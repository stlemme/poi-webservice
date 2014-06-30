<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');

$dp = new POIDataProvider();

$components = $dp->getSupportedComponents();
Response::json(array("components" => $components));

?>