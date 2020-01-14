<?php

$failed_data_count_check = $module->failed_data_count_check($_GET);
$content = json_encode($failed_data_count_check);

RestUtility::sendResponse(200, $content);

?>
