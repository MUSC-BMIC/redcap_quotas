<?php

namespace MUSC\QuotaConfig;

use REDCap;

class QuotaConfig extends \ExternalModules\AbstractExternalModule {

    function redcap_every_page_top(int $project_id) {
        if (strpos(PAGE, 'ExternalModules/manager/project.php') !== false) {
            $this->setJsSettings('quotaConfigSettings', array('modulePrefix' => $this->PREFIX, 'useOldVal' => 'false'));

            // Get all field variable names in project
            // Get the data dictionary for the current project in array format
            $dd_array = REDCap::getDataDictionary('array');
            $this->setJsSettings('quotaConfigFields', $dd_array);

            $this->includeJs('js/quota_config.js');
        }
    }

    function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
        $config = $this->getProjectSettings();

        echo '
    <div id="quota-modal" class="modal fade" role="dialog" data-backdrop="static">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Eligibility <span class="module-name"></span></h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-defaultrc" id="btnCloseCodesModalDelete" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>';

        $qes = array(
            'url' => $this->getUrl('quota_enforcer.php', true, true),
            'accepted' => $config['accepted']['value'],
            'rejected' => $config['rejected']['value'],
            'quota_met_indicator' => $config['quota_met_indicator']['value']
        );

        $this->setJsSettings('quotaEnforcementSettings', $qes);
        $this->includeJs('js/quota_enforcer.js');
    }

    function current_quota_for($params) {
        $config = $this->getProjectSettings();

        $total_n_met = $this->total_n_quota_met($config);
        $generic_quotas_newly_violated = $this->generic_quotas_newly_violated($config, $params);

        // Consider quota met if total n quota met or any generic quota is met
        $quota_met = $total_n_met || $generic_quotas_newly_violated;
        return $quota_met;
    }

    function total_n_quota_met($config) {
        $total_n = $config['quota_n']['value'];
        $total_n_enforced = $config['quota_n_enforced']['value'];

        $total_data_count = $this->dataCount($config['included_in_quota_n']['value']);
        return ($total_n_enforced == true) && ($total_data_count >= $total_n);
    }

    function generic_quotas_newly_violated($config, $request_params) {
        $params = array('return_format' => 'array');
        $existing_data = REDCap::getData($params);

        $total_n = $config['quota_n']['value'];
        $total_n_enforced = $config['quota_n_enforced']['value'];
        $existing_n = count($existing_data);

        $field_names = $config['field_name']['value'];
        $field_operators = $config['field_operator']['value'];
        $field_quantifiers = $config['field_quantifier']['value'];
        $field_quantities = $config['field_quantity']['value'];
        $fields_selected = $config['field_selected']['value'];

        // Iterate through all generic quotas
        for ($i = 0; $i < count($field_names); $i++) {
            // The arithmetic operator to use when determining if the quotas's been met
            // (value will be one of `=`, `<`, `<=`, `<>`, `>=`, `>`)
            $field_operator = $field_operators[$i];

            // Determines whether to enforce the quota based on the number of matching
            // records or based on the percentage of total records that match (value
            // will either be `total` or `%`)
            $field_quantifier = $field_quantifiers[$i];

            // The target number or percent that is the limit for the current quota
            $field_quantity = intval($field_quantities[$i]);

            $quota_newly_violated = $this->generic_quota_newly_violated(
                    $request_params,
                    $existing_data,
                    $field_names[$i],
                    $fields_selected[$i],
                    $field_operator,
                    $field_quantifier,
                    $field_quantity,
                    $total_n,
                    $total_n_enforced,
                    $existing_n);

            if ($quota_newly_violated) {
                return true;
            }
        }

        return false;
    }

    private function generic_quota_newly_violated(
            $request_params,
            $existing_data,
            $field_names,
            $field_values,
            $field_operator,
            $field_quantifier,
            $field_quantity,
            $total_n,
            $total_n_enforced,
            $existing_n) {

        $matching_records = 0;
        $total_records = count($existing_data) + 1;
        $event_id = intval($request_params['event_id']);

        // Figure out of the data contained in the request matches the current quota. If not, the current
        // quota has no bearing on the new request and shouldn't ever be a violation
        $request_matches = $this->record_matches($request_params, $field_names, $field_values);

        if (!$request_matches) {
            return false;
        }

        // Iterate through all existing records to find how many match the current quota
        foreach ($existing_data as $event_record) {
            // Actual record is nested under the event Id
            $record = $event_record[$event_id];

            $record_matches = $this->record_matches($record, $field_names, $field_values);

            // If the current record matched all fields in the specified quota, consider it a
            // match and increment this counter
            if ($record_matches) {
                $matching_records++;
            }
        }

        // By default, use the number of matching records to evaluate if the quota has been met
        $previous_operand = $matching_records;
        $new_operand = $matching_records + 1;

        // If the quantifier for the current quota is `%`, use the number of matching records
        // as a percentage of the total number of records to evaluate if the quota has been met
        if ($field_quantifier == '%') {
            $previous_operand = $previous_operand / $total_records;
            $new_operand = $new_operand / $total_records;
        }

        // Need to determine if the quota was already violated before the addition of the new
        // record as well as whether it will be violated with the addition of the new record. This
        // will stop us from incorrectly preventing new records in situations where, for example,
        // the quota is "at least 10 women" and we currently have 6 women 
        // (so techincally this would be violating the quota). We wouldn't want to
        // stop the user from adding more women then because they're getting closer to fulfilling
        // the quota. However, this might get us into trouble in scenarios where the quota is "at
        // most 10 women" and we current have 12 women. We will not be considering that a new violation
        // as things stand and that might be a problem
        $quota_previously_violated = $this->quota_arithmetic_violated(
                $previous_operand,
                $field_operator,
                $field_quantity,
                $total_n,
                $total_n_enforced,
                $existing_n);
        
        $quota_now_violated = $this->quota_arithmetic_violated(
                $new_operand,
                $field_operator,
                $field_quantity,
                $total_n,
                $total_n_enforced,
                $existing_n);

        // This value tracks if the quota was NOT violated by the data existing in the database
        // but IS violated by the addition of the request data to that of the data in the database
        return (!$quota_previously_violated and $quota_now_violated);
    }

    private function quota_arithmetic_violated(
            $operand,
            $operator,
            $quantity,
            $total_n,
            $total_n_enforced,
            $existing_n) {
        
        if ($operator == '=') {
            // Operation isn't well defined
            return ($operand != $quantity);
        }

        if ($operator == '>') {
            if ($operand > $quantity or !$total_n_enforced) {
                return false;
            } else {
                return ($quantity - $operand) >= $total_n - $existing_n;
            }
        }

        if ($operator == '>=' or !$total_n_enforced) {
            if ($operand >= $quantity) {
                return false;
            } else {
                return ($quantity - $operand) > $total_n - $existing_n;
            }
        }

        if ($operator == '<') {
            return ($operand >= $quantity);
        }

        if ($operator == '<=') {
            return ($operand > $quantity);
        }

        if ($operator == '<>') {
            // Operation isn't well defined
            return ($operand == $quantity);
        }

        return false;
    }

    private function record_matches($record, $field_names, $field_values) {
        // Need to iterate through all field names on the current quota to account for
        // nested quotas
        for ($j = 0; $j < count($field_names); $j++) {
            // The property on the record to enforce a quota on (example: `sex`, `age)
            $field_name = $field_names[$j];

            // The value to match records against (example: `Female`, 15)
            $field_value = $field_values[$j];

            if ($record[$field_name] != $field_value) {
                // If the current record does not match the specified value for any field
                // specified in the quota, we don't consider it a match
                return false;
            }
        }

        return true;
    }

    protected function dataCount($included_in_quota_n) {
        $params = array('return_format' => 'array', 'fields' => array('record_id'));

        // if we set a variable to indicate the record should be included in the total_n count, use it to filter the data returned
        if ($included_in_quota_n != '') {
            $params = array('return_format' => 'array', 'filterLogic' => "[$included_in_quota_n] = '1'", 'fields' => array('record_id'));
        }

        $data = REDCap::getData($params);
        return count($data);
    }

    protected function setJsSettings($var, $settings) {
        echo '<script>' . $var . ' = ' . json_encode($settings) . ';</script>';
    }

    protected function includeJs($path) {
        echo '<script src="' . $this->getUrl($path) . '"></script>';
    }

}

?>
