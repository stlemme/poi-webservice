<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');


$dp = POIDataProvider::getInstance();
$req = $dp->request();

$req->checkMethod('POST', "You must use HTTP POST for adding a new POI!");

$poi_data = $req->body();
$poi_info = $dp->create($poi_data);

Response::json(array("created_poi" => $poi_info));

?>