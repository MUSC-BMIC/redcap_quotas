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

    $this->setJsSettings('quotaEnforcementSettings', array('url' => $this->getUrl('quota_enforcer.php', true, true), 'accepted' => $config['accepted']['value'], 'rejected' => $config['rejected']['value'], 'quota_met_indicator' => $config['quota_met_indicator']['value']));
    $this->includeJs('js/quota_enforcer.js');
  }

  function current_quota_for($params)
  {
    $config = $this->getProjectSettings();

    $total_n = $config['quota_n']['value'];
    $total_n_enforced = $config['quota_n_enforced']['value'];

    $data = REDCap::getData('array');

    $total_n_met = ($total_n_enforced == true) && (count($data) >= $total_n);

    // another quota check
    // $dob_quoata = true;

    // $quota_met = $total_n_met || $dob_quota;
    $quota_met = $total_n_met;
    return $quota_met;
  }

  protected function setJsSettings($var, $settings) {
    echo '<script>' . $var . ' = ' . json_encode($settings) . ';</script>';
  }

  protected function includeJs($path) {
    echo '<script src="' . $this->getUrl($path) . '"></script>';
  }
}

?>
