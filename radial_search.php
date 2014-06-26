<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');
require_once(__DIR__ . '/lib/selector/radial-selector.php');


$dp = new POIDataProvider();

$spatialIndex = $dp->getSpatialIndex();
$sel = new RadialSelector($spatialIndex);

$data = $dp->query($sel, $_GET);

// TODO: stream line processing using iterators and incremental json output
// TODO: support jsonp
Response::json(array("pois" => $data));

// if (isset($params['jsoncallback'])) {
	// Response::jsonp($params['jsoncallback'], array('pois' => $pois));
// } else {
	// Response::json(array('pois' => $pois));
// }

?>