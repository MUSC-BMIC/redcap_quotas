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
    $this->setJsSettings('quotaEnforcementSettings', array('url' => $this->getUrl('quota_enforcer.php', true, true)));
    $this->includeJs('js/quota_enforcer.js');
  }

  function current_quota_for($params)
  {
    $config = $this->getProjectSettings();

    $total_n = $config['quota_n']['value'];
    $total_n_enforced = $config['quota_n_enforced']['value'];

    $data = REDCap::getData('array');

    $total_n_met = ($total_n_enforced == true) && (count($data) >= $total_n);
    return array('totalNMet' => $total_n_met, 'totalN' => $total_n, 'totalNEnforced' => $total_n_enforced);
  }

  protected function setJsSettings($var, $settings) {
    echo '<script>' . $var . ' = ' . json_encode($settings) . ';</script>';
  }

  protected function includeJs($path) {
    echo '<script src="' . $this->getUrl($path) . '"></script>';
  }
}

?>
