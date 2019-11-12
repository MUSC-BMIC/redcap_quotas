<?php

namespace MUSC\QuotaConfig;
use REDCap;

class QuotaConfig extends \ExternalModules\AbstractExternalModule
{
  function redcap_every_page_top(int $project_id)
  {
    if (strpos(PAGE, 'ExternalModules/manager/project.php') !== false)
    {
      $this->setJsSettings('quotaConfigSettings', array('modulePrefix' => $this->PREFIX, 'useOldVal' => 'false'));

      // Get all field variable names in project
      // Get the data dictionary for the current project in array format
      $dd_array = REDCap::getDataDictionary('array');
      $this->setJsSettings('quotaConfigFields', $dd_array);

      $this->includeJs('js/quota_config.js');
    }
  }

  function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
  {
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

  function current_quota_for($params)
  {
    $config = $this->getProjectSettings();

    $total_n_met = $this->total_n_quota_met($config);
    $generic_quotas_met = $this->generic_quotas_met($config, $params);

    // $quota_met = $total_n_met || $dob_quota;
    $quota_met = $total_n_met || $generic_quotas_met;
    return $quota_met;
  }

  function total_n_quota_met($config)
  {
    $total_n = $config['quota_n']['value'];
    $total_n_enforced = $config['quota_n_enforced']['value'];

    $total_data_count = $this->dataCount($config['included_in_quota_n']['value']);
    return ($total_n_enforced == true) && ($total_data_count >= $total_n);
  }

  function generic_quotas_met($config, $request_params)
  {
    $params = array('return_format' => 'array');
    $data = REDCap::getData($params);
    $total_count = count($data);

    $field_names = $config['field_name']['value'];
    $field_operators = $config['field_operator']['value'];
    $field_quantifiers = $config['field_quantifier']['value'];
    $field_quantities = $config['field_quantity']['value'];
    $fields_selected = $config['field_selected']['value'];

    $event_id = intval($request_params['event_id']);

    $quotas_met = false;

    for ($i = 0; $i < count($field_names); $i++)
    {
      $field_name = $field_names[$i][0];
      $field_operator = $field_operators[$i];
      $field_quantifier = $field_quantifiers[$i];
      $field_quantity = intval($field_quantities[$i]);
      $field_selected = $fields_selected[$i][0];
      $matching_records = 0;

      foreach ($data as $record)
      {
        $record = $record[$event_id];
        if ($record[$field_name] == $field_selected)
        {
            $matching_records++;
        }
      }

      if ($request_params[$field_name] == $field_selected)
      {
        $matching_records++;
      }

      $operand = $matching_records;
      if ($field_quantifier == '%')
      {
        $operand = $matching_records / $total_count;
      }

      $quota_met = false;
      if ($field_operator == '=')
      {
        $quota_met = ($operand >= $field_quantity);
      }

      if ($field_operator == '>')
      {
        $quota_met = ($operand < $field_quantity);
      }

      if ($field_operator == '>=')
      {
        $quota_met = ($operand <= $field_quantity);
      }

      if ($field_operator == '<')
      {
        $quota_met = ($operand > $field_quantity);
      }

      if ($field_operator == '<=')
      {
        $quota_met = ($operand >= $field_quantity);
      }

      if ($field_operator == '<>')
      {
        $quota_met = ($operand != $field_quantity);
      }

      $quotas_met = ($quotas_met or $quota_met);
    }

    return $quotas_met;
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
