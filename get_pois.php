<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');
require_once(__DIR__ . '/lib/selector/id-selector.php');


$dp = new POIDataProvider();

$sel = new IdSelector();
$data = $dp->query($sel, $_GET);

Response::json(array("pois" => $data));

?>