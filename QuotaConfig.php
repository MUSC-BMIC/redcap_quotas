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

    // extract creates local variables (eg. $qc_quota_n), you still have to use ['value'] to get the value of the variable
    extract($config, EXTR_PREFIX_ALL, 'qc');
    print "<div>";
    print "<br />";
    print "<br />";
    print "<br />";
    print_r($config);
    print "<br />";
    print "<br />";
    print $qc_quota_n['value'];
    print "<br />";
    print $qc_quota_n_enforced['value'];
    print_r($qc_field_name['value'][0]);
    print "<br />";
    print "<br />";
    print "<br />";
    print "</div>";


  }

  protected function setJsSettings($var, $settings) {
    echo '<script>' . $var . ' = ' . json_encode($settings) . ';</script>';
  }

  protected function includeJs($path) {
    echo '<script src="' . $this->getUrl($path) . '"></script>';
  }
}

?>
