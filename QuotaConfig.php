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

    function failed_quota_check($params) {
        $config = $this->getProjectSettings();

        $maximum_sample_size_obtained = $this->maximum_sample_size_obtained($config);

        // Consider quota met if total n quota met or any generic quota is met
        return $maximum_sample_size_obtained; //|| $generic_quotas_newly_violated;
    }

    function maximum_sample_size_obtained($config) {
        $maximum_sample_size = $config['maximum_sample_size']['value'];
        $total_data_count = $this->dataCount($config['passed_quota_check']['value'], $config['confirmed_enrollment']['value']);

        return ($total_data_count >= $maximum_sample_size);
    }

    protected function dataCount($passed_quota_check, $confirmed_enrollment) {
        $params = array('return_format' => 'array', 'filterLogic' => "[$passed_quota_check] = '1'", 'fields' => array('record_id'));

        if ($confirmed_enrollment != '') {
            $params = array('return_format' => 'array', 'filterLogic' => "[$passed_quota_check] = '1' AND [$confirmed_enrollment] = '1'", 'fields' => array('record_id'));
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
