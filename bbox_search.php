<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');
require_once(__DIR__ . '/lib/filter/category-filter.php');


$dp = POIDataProvider::getInstance();

$req = $dp->request(
	array('north', 'south', 'east', 'west'),
	array('component', 'jsoncallback', 'max_results', 'category')
);

$req->checkMethod('GET', "You must use HTTP GET for retrieving POIs!");

$params = $req->parseParams($_GET);
$components = isset($params['component']) ? $params['component'] : $dp->getSupportedComponents();
$max_results = isset($params['max_results']) ? $params['max_results'] : $dp->config('max_results');

$north = $params['north'];
$south = $params['south'];
$west  = $params['west'];
$east  = $params['east'];


$spatialIndex = $dp->getSpatialIndex();

if ($spatialIndex == null)
	Response::fail(400, "Spatial queries are not supported.");
	
$filters = array();

if (isset($params['category']))
	$filters[] = new CategoryFilter($params['category']);

// TODO: check west < east and south < north

$spatialResult = $spatialIndex->bbox_search($west, $east, $south, $north);

$pois = array();
$results = 0;

foreach($spatialResult as $uuid)
{
	if ($results >= $max_results)
		break;
	
	$poi_data = array();
	
	foreach($filters as $filter) {
		if (!$dp->applyFilter($uuid, $poi_data, $filter)) {
			$uuid = null;
			break;
		}
	}
	
	if ($uuid == null)
		continue;
	
	$dp->complete($uuid, $poi_data, $components);
	
	$pois[$uuid] = $poi_data;
	$results++;
}

// TODO: stream line processing using iterators and incremental json output

if (isset($params['jsoncallback'])) {
	Response::jsonp($params['jsoncallback'], array('pois' => $pois));
} else {
	Response::json(array('pois' => $pois));
}

?>