<?php

$file = 'Instuctions_for_CheatBlocker_and_Quota_Combined.pdf';
$path = $module->getURL($file);

header("Content-Type: application/pdf");
header('Content-Disposition: inline; filename="'.$file.'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');

@readfile($path);

?>
