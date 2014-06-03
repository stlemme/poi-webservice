<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');

$dp = POIDataProvider::getInstance();

$components = $dp->getSupportedComponents();
Response::json(array("components" => $components));

?>