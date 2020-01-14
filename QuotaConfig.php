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

      $this->setJsSettings('quotaConfigFields', $dd_array);
      $this->includeJs('js/quota_config.js');
    }
  }

  // this pertains to data entry forms and surveys
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
      'passed_quota_check' => $config['passed_quota_check']['value']
    );

    $this->setJsSettings('quotaEnforcementSettings', $qes);
    $this->includeJs('js/quota_enforcer.js');
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

    $quota_sample_sizes_obtained = $this->quota_sample_sizes_obtained($config, $maximum_sample_size, $total_data_count, $filter_logic, $params);

    $maximum_sample_size_obtained = $this->maximum_sample_size_obtained($maximum_sample_size, $total_data_count);

    $return_val = $maximum_sample_size_obtained || $quota_sample_sizes_obtained;
    return $return_val;
  }

  // need to rename this method and incorporate non-quota sample sizes (residual space) TODO TODO TODO
  function quota_sample_sizes_obtained($config, $maximum_sample_size, $total_data_count, $filter_logic, $params) {
    $field_names = $config['field_name']['value'];
    $fields_selected = $config['field_selected']['value'];
    $field_quantities = $config['field_quantity']['value'];
    $field_quantity_types = $config['field_quantity_type']['value'];

    $obtained = array();

    $x = array();

    for($j = 0; $j < count($field_names); $j++) {
      $x[$j] = array('field_quantity' => $field_quantities[$j], 'field_quantity_type' => $field_quantity_types[$j], 'quotas' => array());

      for($k = 0; $k < count($field_names[$j]); $k++) {
        $x[$j]['quotas'][$field_names[$j][$k]] = $fields_selected[$j][$k];
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

      $diff = array_diff_assoc($quotas, $params);

      //print_r($quotas);
      //print_r($diff);

      if(empty($diff)) {

        foreach($quotas as $key => $value) {
          $quota_filter_logic .= " AND [$key] = '$value'";
        }

        $quota_data_count = $this->dataCount($quota_filter_logic);

        if($quota_data_count >= $field_quantity) {
          array_push($obtained, 1);
        }
        else {
          array_push($obtained, 0);
        }
      }
    }

    //print_r($obtained);

    $obtained = array_unique($obtained);

    $return_val = (count($obtained) === 1 && end($obtained) === 1);
    return $return_val;
  }

  function maximum_sample_size_obtained($maximum_sample_size, $total_data_count) {
    return ($total_data_count >= $maximum_sample_size);
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

}

?>
