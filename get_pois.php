<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');


$dp = POIDataProvider::getInstance();

$req = $dp->request(
	array('poi_id'),
	array('component')
);

$req->checkMethod('GET', "You must use HTTP GET for retrieving POIs!");

$params = $req->parseParams($_GET);
$components = isset($params['component']) ? $params['component'] : $dp->getSupportedComponents();

$data = array();

foreach($params['poi_id'] as $poi_uuid)
    $data[$poi_uuid] = $dp->read($poi_uuid, $components);

Response::json(array("pois" => $data));

?>