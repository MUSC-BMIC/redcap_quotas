<?php

$current_quota = $module->current_quota_for($_GET);
$content = json_encode($current_quota);

RestUtility::sendResponse(200, $content);

?>
