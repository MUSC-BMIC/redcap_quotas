<?php

namespace MUSC\QuotaConfig;

use REDCap;

class QuotaConfig extends \ExternalModules\AbstractExternalModule {

  // this only pertains to the setup page for QuotaConfig
  function redcap_every_page_top(int $project_id) {
    if (strpos(PAGE, 'ExternalModules/manager/project.php') !== false) {
      $this->setJsSettings('quotaConfigSettings', array('modulePrefix' => $this->PREFIX, 'useOldVal' => 'false'));

      // Get all field variable names in project
      // Get the data dictionary for the current project in array format
      $dd_array = REDCap::getDataDictionary('array');

      $filtered_dd_array = array();

      foreach ($dd_array as $field_name => $field_attributes) {
        if (($field_attributes['field_type'] == 'dropdown') || ($field_attributes['field_type'] == 'radio')) {
          array_push($filtered_dd_array, $field_name);
        }
      }

      $this->setJsSettings('quotaConfigFields', $dd_array);
      $this->setJsSettings('quotaConfigValidFieldNameOptions', $filtered_dd_array);
      $this->includeJs('js/quota_config.js');
      $this->includeCss('css/config.css');
    }
  }

  function init_page_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
  // this pertains to data entry forms and surveys
    $config = $this->getProjectSettings();
    $modal_title = $config['modal_title']['value'];

    echo "
  <div id='quota-modal' class='modal fade' role='dialog' data-backdrop='static'>
    <div class='modal-dialog'>
      <div class='modal-content'>
        <div class='modal-header'>
          <h4 class='modal-title'>$modal_title<span class='module-name'></span></h4>
          <button type='button' class='close' data-dismiss='modal'>&times;</button>
        </div>
        <div class='modal-body'></div>
        <div class='modal-footer'>
          <button type='button' class='btn btn-defaultrc' id='btnCloseCodesModalDelete' data-dismiss='modal'>Continue</button>
        </div>
      </div>
    </div>
  </div>";

    $qes = array(
      'url' => $this->getUrl('quota_enforcer.php', true, true),
      'accepted' => $config['accepted']['value'],
      'rejected' => $config['rejected']['value'],
      'passed_quota_check' => $config['passed_quota_check']['value']
    );

    $this->setJsSettings('quotaEnforcementSettings', $qes);
    $this->includeJs('js/quota_enforcer.js');
  }

  function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
    $config = $this->getProjectSettings();
    $passed_quota_check = $config['passed_quota_check']['value'];
    $confirmed_enrollment = $config['confirmed_enrollment']['value'];

    // this is a new record
    if (is_null($record)) {
      $this->init_page_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance);
    }
    else {
      $fields = array($passed_quota_check);

      if ($confirmed_enrollment != '') {
        array_push($fields, $confirmed_enrollment);
      }

      $params = array('return_format' => 'array', 'records' => $record, 'fields' => $fields);

      $data = REDCap::getData($params);
      $record_data = $data[$record][$event_id];

      if ($confirmed_enrollment != '') {
        if ($record_data[$confirmed_enrollment] == 0) {
          $this->init_page_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance);
        }
      }
      else {
        if ($record_data[$passed_quota_check] == 0) {
          $this->init_page_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance);
        }

      }
    }
  }

  function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
    $this->init_page_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance);
  }

  function failed_data_count_check($params) {
    $config = $this->getProjectSettings();

    $maximum_sample_size = $config['maximum_sample_size']['value'];

    $passed_quota_check = $config['passed_quota_check']['value'];
    $confirmed_enrollment = $config['confirmed_enrollment']['value'];
    $filter_logic = "[$passed_quota_check] = '1'";

    if ($confirmed_enrollment != '') {
      $filter_logic .= " AND [$confirmed_enrollment] = '1'";
    }

    $total_data_count = $this->dataCount($filter_logic);

    $sample_sizes_obtained = $this->sample_sizes_obtained($config, $maximum_sample_size, $total_data_count, $filter_logic, $params);

    $maximum_sample_size_obtained = $this->maximum_sample_size_obtained($maximum_sample_size, $total_data_count);

    $return_val = $maximum_sample_size_obtained || $sample_sizes_obtained;
    return $return_val;
  }

  // need to rename this method and incorporate non-quota sample sizes (residual space) TODO TODO TODO
  function sample_sizes_obtained($config, $maximum_sample_size, $total_data_count, $filter_logic, $params) {
    $field_names = $config['field_name']['value'];
    $fields_selected = $config['field_selected']['value'];
    $field_quantities = $config['field_quantity']['value'];
    $field_quantity_types = $config['field_quantity_type']['value'];
    $field_negated = $config['field_negated']['value'];

    $obtained = array();

    // max number based on configuration and total number actually being utilized
    $maximum_quota_related_sample_size = 0;
    $total_quota_data_count = 0;

    $x = array();

    for($j = 0; $j < count($field_names); $j++) {
      $x[$j] = array('field_quantity' => $field_quantities[$j], 'field_quantity_type' => $field_quantity_types[$j], 'quotas' => array());

      for($k = 0; $k < count($field_names[$j]); $k++) {
        // Need to associate both the selected value and the negation with each
        // selected field in the quota
        $x[$j]['quotas'][$field_names[$j][$k]] = array('field_selected' => $fields_selected[$j][$k], 'field_negated' => ($field_negated[$j][$k] == 1));
      }
    }

    for($l = 0; $l < count($x); $l++) {
      $quota_filter_logic = $filter_logic;
      $field_quantity = $x[$l]['field_quantity'];
      $field_quantity_type= $x[$l]['field_quantity_type'];

      switch ($field_quantity_type) {
      case "%":
        $field_quantity = ($maximum_sample_size * $field_quantity) / 100.0;
        break;
      }

      $quotas = $x[$l]['quotas'];

      // Since $quotas is now key => [value, negation] instead of key => value,
      // we need to define a custom diff function that accounts for negation
      $diff = $this->quota_diff_assoc($quotas, $params);

      foreach($quotas as $key => $values) {
        $value = $values['field_selected'];
        $negated = $values['field_negated'];
        if ($negated) {
            $quota_filter_logic .= " AND [$key] <> '$value'";
        } else {
            $quota_filter_logic .= " AND [$key] = '$value'";
        }
      }

      $quota_data_count = $this->dataCount($quota_filter_logic);

      if ($quota_data_count > $field_quantity) {
        $quota_data_count = $field_quantity;
      }

      $maximum_quota_related_sample_size += $field_quantity;
      $total_quota_data_count += $quota_data_count;

      if(empty($diff)) {

        if($quota_data_count >= $field_quantity) {
          array_push($obtained, 1);
        }
        else {
          array_push($obtained, 0);
        }
      }
    }

    // as long as we have 1 free spot available that isn't quota related we can allow this record to have it
    // 150 mss, 100  = 50 slots free,  75 slots total, 50 quota slots total,  75 - 50 = 25 non-quota slots are used

    $non_quota_data_count = $total_data_count - $total_quota_data_count;
    $non_quota_sample_size = $maximum_sample_size - $maximum_quota_related_sample_size;

    if ($non_quota_sample_size > $non_quota_data_count) {
      array_push($obtained, 0);
    }
    else {
      array_push($obtained, 1);
    }

    $obtained = array_unique($obtained);

    $return_val = (count($obtained) === 1 && end($obtained) === 1);
    return $return_val;
  }

  function maximum_sample_size_obtained($maximum_sample_size, $total_data_count) {
    return ($total_data_count >= $maximum_sample_size);
  }
  
  /*
   * When making this diff, the $params object will be something along the lines of:
   * $params = {
   *    'sex': 0,
   *    'race': 3,
   *    ...
   * }
   * and $quotas will now be something like:
   * $quotas = {
   *    'sex': [1 (value), 0 (negated)],
   *    ... 
   * }
   * 
   * so we need a custom diff function that will compare the quota with the params
   * values and account for the negation. Elements in the list will be added to the
   * diff result in two cases:
   * 1) The value in $params does NOT match the value for the same key in $quotas AND negation is false
   * 2) The value in $params DOES match the value for the same key in $quotas AND negation is true
   */
  protected function quota_diff_assoc($quotas, $params) {
      $diff = array();
      foreach($quotas as $key => $values) {
          $value = $values['field_selected'];
          $negated = $values['field_negated'];
          if (($negated && $params[$key] == $value) || (!$negated && $params[$key] != $value)) {
                $diff[$key] = $value;
          }
      }
      
      return $diff;
  }

  protected function dataCount($filter_logic) {
    $params = array('return_format' => 'array', 'filterLogic' => $filter_logic, 'fields' => array('record_id'));
    $data = REDCap::getData($params);
    return count($data);
  }

  protected function setJsSettings($var, $settings) {
    echo '<script>' . $var . ' = ' . json_encode($settings) . ';</script>';
  }

  protected function includeJs($path) {
    echo '<script src="' . $this->getUrl($path) . '"></script>';
  }

  protected function includeCss($path) {
        echo '<link rel="stylesheet" href="' . $this->getUrl($path) . '">';
  }

}

?>
