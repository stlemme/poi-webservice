<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/request.php');
// require_once(__DIR__ . '/lib/response.php');


$dp = new POIDataProvider();
$req = new Request(array(
	'required' => array(
		'poi_id' => array(
			'type' => 'uuid',
			'array' => true
		)
	)
));

$req->checkMethod('DELETE', "You must use HTTP DELETE for deleting POIs!");

$poi_ids = $params['poi_id'];

$dp->delete($poi_ids);

// TODO: return an appropriate response
echo "POI deleted successfully";
// e.g. Response::json(array("deleted_poi" => true));

?>