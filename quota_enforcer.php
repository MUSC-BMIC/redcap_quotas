<?php
$failed_data_count_check = $module->failed_data_count_check($_GET);

//Check is cheat_blocker module is enabled
//If enabled, get the data from cheat_blocker module
$enabledModules = \ExternalModules\ExternalModules::getEnabledModules($_GET['pid']);
if (isset($enabledModules['redcap_cheat_blocker'])){
  $cheat_module = \ExternalModules\ExternalModules::getModuleInstance('redcap_cheat_blocker');
  $cheat_content = $cheat_module->check_for_duplicates($_GET);

  //push the values into the existing array
  $failed_data_count_check = array_merge($failed_data_count_check, $cheat_content);
}

$content = json_encode($failed_data_count_check);
RestUtility::sendResponse(200, $content);

?>
