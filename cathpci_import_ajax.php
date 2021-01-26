<?php
header('Content-Type: application/json;charset=utf-8');

$module->llog('receieved cathpci_import_ajax reqeust: ' . date('c'));

$response = new \stdClass();
list($response->success, $response->msg) = $module->importChunk();
exit(json_encode($response));
?>