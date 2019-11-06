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

    $total_n = $config['quota_n']['value'];
    $total_n_enforced = $config['quota_n_enforced']['value'];

    $total_data_count = $this->dataCount($config['included_in_quota_n']['value']);
    $total_n_met = ($total_n_enforced == true) && ($total_data_count >= $total_n);

    // another quota check
    // $dob_quoata = true;

    // $quota_met = $total_n_met || $dob_quota;
    $quota_met = $total_n_met;
    return $quota_met;
  }

  protected function dataCount($included_in_quota_n) {
    $params = array('return_format' => 'array', 'fields' => array('record_id'));

    // if we set a variable to indicate the record should be included in the total_n count, use it to filter the data returned
    if ($included_in_quota_n != '') {
      $params = array('return_format' => 'array', 'filterLogic' => "[$included_in_quota_n] = '1'", 'fields' => array('record_id'));
    };

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
