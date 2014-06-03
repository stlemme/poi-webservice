<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
// require_once(__DIR__ . '/lib/response.php');


$dp = POIDataProvider::getInstance();
$req = $dp->request(
	array('poi_id')
);

$req->checkMethod('DELETE', "You must use HTTP DELETE for deleting POIs!");

$poi_ids = $params['poi_id'];

$dp->delete($poi_ids);

// TODO: return an appropriate response
echo "POI deleted successfully";
// e.g. Response::json(array("deleted_poi" => true));

?>