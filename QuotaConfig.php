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
      $allowable_field_types = array('dropdown', 'radio', 'calc');

      foreach ($dd_array as $field_name => $field_attributes) {
        if (in_array($field_attributes['field_type'], $allowable_field_types)) {
          array_push($filtered_dd_array, $field_name);
        }
      }

      //Check if both the modules are enabled to show/hide combined data dictionary link
      //and pass it to quota_config.js file
      $enabledModules = \ExternalModules\ExternalModules::getEnabledModules($_GET['pid']);
      $both_enabled = isset($enabledModules['cheat_blocker'])? true : false;
      $both_document_path = $this->getURL('Instuctions_for_CheatBlocker_and_Quota_Combined.pdf');

      $this->setJsSettings('quotaConfigFields', $dd_array);
      $this->setJsSettings('quotaConfigValidFieldNameOptions', $filtered_dd_array);
      $this->setJsSettings('both_enabled', $both_enabled);
      $this->setJSSettings('both_document_path', $both_document_path);
      $this->includeJs('js/quota_config.js');
      $this->includeJs('js/bootstrap-select.min.js');
      $this->includeCss('css/config.css');
      $this->includeCss('css/bootstrap-select.min.css');
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
      'eligibility_message' => $config['eligibility_message']['value'],
      'passed_quota_check' => $config['passed_quota_check']['value'],
      'confirmed_enrollment' => $config['confirmed_enrollment']['value']
    );

    $this->setJsSettings('quotaEnforcementSettings', $qes);
    // $this->includeJs('js/quota_enforcer.js');

     // check if cheat_blocker plugin is enabled
    $enabledModules = \ExternalModules\ExternalModules::getEnabledModules($_GET['pid']);
    if (isset($enabledModules['cheat_blocker'])){
      $cheat_module = \ExternalModules\ExternalModules::getModuleInstance('cheat_blocker');

      $cheat_config = $cheat_module->getProjectSettings();
      $cheatSettings = array(
        'potential_duplicate_message' => $cheat_config['potential_duplicate_message']['value']
      );

      $this->setJsSettings('cheatSettings', $cheatSettings);
      $this->includeJs('quotaconfig_cheatblocker.js');
    }
    else{
      $this->includeJs('js/quota_enforcer.js');
    }
  }

  function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
    $config = $this->getProjectSettings();
    $passed_quota_check = $config['passed_quota_check']['value'];
    $confirmed_enrollment = $config['confirmed_enrollment']['value'];

    // Check if cheat_blocker plugin is enabled, go to init_page_top if enabled
    // If it's a new record, go to init_page_top
    $enabledModules = \ExternalModules\ExternalModules::getEnabledModules($_GET['pid']);

    if (isset($enabledModules['cheat_blocker']) || is_null($record)){
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
          $this->init_page_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance);
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
    $block_size = $config['block_size']['value'];
    $passed_quota_check = $config['passed_quota_check']['value'];
    $confirmed_enrollment = $config['confirmed_enrollment']['value'];
    $filter_logic = "[$passed_quota_check] = '1'";
    $participant_enrolled = $params['participant_enrolled']['value'];

    if ($confirmed_enrollment != ''){
      $filter_logic .= " AND [$confirmed_enrollment] = '1'";
    }

    $total_data_count = $this->dataCount($filter_logic);
    $maximum_sample_size_reached = ($total_data_count >= $maximum_sample_size);
    $block_number = 0;

    // If the maximum sample size has already been reached then no more
    // submissions should be accepted
    if ($maximum_sample_size_reached) {
      return array(failed_data_check_count => true, block_number => -1);
    }

    if($confirmed_enrollment != ''){

      //If the record is not confirmed yet, show eligibility message
      if($participant_enrolled == ''){
        return array(failed_data_check_count => false, block_number => -1, eligibility_message => true);
      }

      //If the record is marked as not confirmed, show rejected message
      if($participant_enrolled == '0'){
        return array(failed_data_check_count => true, block_number => -1, participant_enrolled => $participant_enrolled);
      }

      // If the record is marked as confirmed, then get a new block number
      // and set the Confirmed Enrollment variable
      if ($participant_enrolled == '1') {
        $block_number = $this->get_block_num_for_new_record($block_size, $filter_logic);
      }

    }

    // Check to see if block size is defined
    if ($block_size != '') {
      // If the block number is already set, continue to use it, other wise calculate
      // it and use it
      $block_number = $params['block_number'];
      if ($block_number == '' || $block_number == '-1') {
        $block_number = $this->get_block_num_for_new_record($block_size, $filter_logic);
      }

      $filter_logic .= " AND [block_number] = '$block_number'";
    } else {
      // If no block size is configured, just use the maximum sample size as
      // block size
      $block_size = $maximum_sample_size;
    }

    $quotas = $this->generate_quotas_map($config);
    $quotas_not_matched_by_submission = $this->quotas_not_matched_by_submission($quotas, $params);

    if (empty($quotas_not_matched_by_submission)) {
      return array(failed_data_check_count => false, block_number => $block_number, participant_enrolled => $participant_enrolled);
    }

    $unreachable_quotas = $this->unreachable_quotas($quotas, $block_size, $filter_logic, $quotas_not_matched_by_submission);
    $failed_data_check_count = !empty($unreachable_quotas);

    return array(failed_data_check_count => $failed_data_check_count, block_number => $block_number, participant_enrolled => $participant_enrolled);
  }

  /* Iterates through the flat lists of fields and generates a map that's more
   * easily reasoned about. The map will be of the form:
   * Quotas =
   * [
   *   {
   *     'field_quantity': 10,
   *     'field_quantity_type': 'total',
   *     'attributes': {
   *       'race': [
   *         'field_selected': '1',
   *         'field_negated': 0
   *       ],
   *       'gender': [
   *         'field_selected': '0',
   *         'field_negated': 0
   *       ],
   *     }
   *   },
   *   ...
   * ]
   */
  function generate_quotas_map($config) {
    $field_names = $config['field_name']['value'];
    $fields_selected = $config['field_selected']['value'];
    $field_quantities = $config['field_quantity']['value'];
    $field_quantity_types = $config['field_quantity_type']['value'];
    $field_negated = $config['field_negated']['value'];

    $quotas = array();

    for($i = 0; $i < count($field_names); $i++) {
      $quotas[$i] = array(
          'field_quantity'      => $field_quantities[$i],
          'field_quantity_type' => $field_quantity_types[$i],
          'attributes'          => array());

      for($j = 0; $j < count($field_names[$i]); $j++) {
        // Need to associate both the selected value and the negation with each
        // selected field in the quota
        $quotas[$i]['attributes'][$field_names[$i][$j]] = array(
            'field_selected' => $fields_selected[$i][$j],
            'field_negated'  => ($field_negated[$i][$j] == 1));
      }
    }

    return $quotas;
  }

  /*
   * Inspects the data in the current submission to determine if it matches
   * any of the configured quotas. Returns an array containing the indexes of
   * any quotas that are not matched.
   */
  function quotas_not_matched_by_submission($quotas, $params) {
    $not_matched_quotas = array();
    for ($i = 0; $i < count($quotas); $i++) {
      $matches_quota = true;
      foreach ($quotas[$i]['attributes'] as $name => $details) {
        $negated = $details['field_negated'];
        $configured_value = $details['field_selected'];
        $submitted_value = $params[$name];

        if ((!$negated && $configured_value != $submitted_value) ||
            ($negated && $configured_value == $submitted_value)) {
          $matches_quota = false;
          break;
        }
      }

      if (!$matches_quota) {
        array_push($not_matched_quotas, $i);
      }
    }

    return $not_matched_quotas;
  }

  /*
   * Checks existing data and determines what block a new submission would
   * belong to.
   */
  function get_block_num_for_new_record($block_size, $filter_logic) {
    $params = array('return_format' => 'array', 'filterLogic' => $filter_logic, 'fields' => array('block_number'));
    $data = REDCap::getData($params);
    $block_counts = array();

    if (count($data) == 0) {
      return 0;
    }

    foreach (array_keys($data) as $index_key) {
      $index_record = $data[$index_key];
      foreach (array_keys($index_record) as $id_key) {
        $id_record = $index_record[$id_key];
        $curr_block_number = $id_record['block_number'];
        if ($curr_block_number == '' || $curr_block_number == '-1') {
          continue;
        }

        if (!array_key_exists($curr_block_number, $block_counts)) {
          $block_counts[$curr_block_number] = 1;
        } else {
          $curr_block_count = $block_counts[$curr_block_number];
          $block_counts[$curr_block_number] = $curr_block_count + 1;
        }
      }
    }

    $max_block_number = max(array_keys($block_counts));
    $max_block_count = $block_counts[$max_block_number];

    if ($max_block_count < $block_size) {
      return $max_block_number;
    }

    return $max_block_number + 1;
  }

  function unreachable_quotas($quotas, $block_size, $filter_logic, $quotas_not_matched_by_submission) {
    $unreachable_quotas = array();
    for ($i = 0; $i < count($quotas_not_matched_by_submission); $i++) {
      $quota_index = $quotas_not_matched_by_submission[$i];
      $quota = $quotas[$quota_index];
      $total_data_count = $this->dataCount($filter_logic);
      $quota_filter_logic = $filter_logic;
      $field_quantity = $quota['field_quantity'];
      $field_quantity_type= $quota['field_quantity_type'];

      switch ($field_quantity_type) {
      case "%":
        $field_quantity = ($block_size * $field_quantity) / 100.0;
        break;
      }

      $attributes = $quota['attributes'];

      foreach($attributes as $name => $details) {
        $negated = $details['field_negated'];
        $configured_value = $details['field_selected'];

        if ($negated) {
            $quota_filter_logic .= " AND [$name] <> '$configured_value'";
        } else {
            $quota_filter_logic .= " AND [$name] = '$configured_value'";
        }
      }

      $existing_quota_data_count = $this->dataCount($quota_filter_logic);
      $num_still_required = $field_quantity - $existing_quota_data_count;
      $spots_remaining = $block_size - $total_data_count;

      if ($spots_remaining <= $num_still_required) {
        array_push($unreachable_quotas, $quota_index);
      }
    }

    return $unreachable_quotas;
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
