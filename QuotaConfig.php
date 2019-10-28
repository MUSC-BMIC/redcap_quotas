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
    // print_r(array_values($config));
    // print "<br />";
    // print "<br />";
    // print $qc_quota_n['value'];
    // echo "$qc_quota_n\n";
    // print "<br />";
    print $qc_accepted['value'];
    // print_r($qc_field_name['value'][0]);
    // echo $qc_accepted_message['value'][0];
    print "<br />";
    print "<br />";
    print "</div>";

    ?>
    <div id="quota-success-modal" class="modal fade" role="dialog" data-backdrop="static">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Eligibility <span class="module-name"></span></h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <?php
            print "<div>";
            print $qc_accepted['value'];
            print "</div>";
            ?>
          </div>
        </div>
      </div>
    </div>
    <div id="quota-failure-modal" class="modal fade" role="dialog" data-backdrop="static">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Eligibility <span class="module-name"></span></h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <?php
            print "<div>";
            print $qc_rejected['value'];
            print "</div>";
            ?>
          </div>
        </div>
      </div>
    </div>
    <?php

    $this->includeJs('js/add_edit_records.js');

  }

  protected function setJsSettings($var, $settings) {
    echo '<script>' . $var . ' = ' . json_encode($settings) . ';</script>';
  }

  protected function includeJs($path) {
    echo '<script src="' . $this->getUrl($path) . '"></script>';
  }
}

?>
