<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');


$dp = POIDataProvider::getInstance();

$req = $dp->request(
	array('lat', 'lon'),
	array('component', 'jsoncallback', 'max_results', 'radius') // begin_time, end_time
);

$req->checkMethod('GET', "You must use HTTP GET for retrieving POIs!");

$params = $req->parseParams($_GET);

$components = isset($params['component']) ? $params['component'] : $dp->getSupportedComponents();
$max_results = isset($params['max_results']) ? $params['max_results'] : $dp->config('max_results');
$radius = isset($params['radius']) ? $params['radius'] : $dp->config('default_radius');

$lon = $params['lon'];
$lat = $params['lat'];


$spatialIndex = $dp->getSpatialIndex();

if ($spatialIndex == null)
	Response::fail(400, "Spatial queries are not supported.");


$spatialResult = $spatialIndex->radial_search($lat, $lon, $radius, false);

$pois = array();
$results = 0;

foreach($spatialResult as $uuid) {
	if ($results >= $max_results)
		break;
	
	// TODO: apply filter to reduce amount of results
	$pois[$uuid] = $dp->read($uuid, $components);
	$results++;
}

if (isset($params['jsoncallback'])) {
	Response::jsonp($params['jsoncallback'], array('pois' => $pois));
} else {
	Response::json(array('pois' => $pois));
}

?>