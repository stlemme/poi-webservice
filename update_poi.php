<?php

require_once(__DIR__ . '/lib/poi-data-provider.php');
require_once(__DIR__ . '/lib/response.php');


$dp = POIDataProvider::getInstance();
$req = $dp->request();

$req->checkMethod('POST', "You must use HTTP POST for updating a POI!");

$poi_update_data = $req->body();

$updated_pois = array();

foreach($poi_update_data as $poi_uuid => $poi_data)
{
	if ($dp->update($poi_uuid, $poi_data))
		$updated_pois[] = $poi_uuid;
}

header("Access-Control-Allow-Origin: *");
// TODO: return an appropriate response
print "POI data updated successfully!";

?>