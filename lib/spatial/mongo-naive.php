<?php

require_once(__DIR__ . '/../spatial-index.php');
require_once(__DIR__ . '/spatial-mongo-result-iterator.php');


class MongoNaive extends SpatialIndex
{
	private $core_comp = 'fw_core';
	
	public function __construct($mongodb) {
		$this->mongodb = $mongodb;
		$this->core = $this->mongodb->selectCollection($this->core_comp);
	}
	
	public function bbox_search($west, $east, $south, $north) {
		$spatialresult = $this->query_bbox($west, $east, $south, $north);
		return new SpatialMongoResultIterator($spatialresult);
	}

	public function radial_search($lat, $lon, $radius, $distance_ordered) {
		$result = array();
		$d = rad2deg($radius / 6371000.0);
		$spatialresult = $this->query_bbox($lon-$d, $lon+$d, $lat-$d, $lat+$d);
		
		// skip POIs with a distance larger than radius
		foreach ($spatialresult as $core_poi_uuid => $fw_core_comp) {
			$coord = $fw_core_comp['location']['wgs84'];
			$dist = $this->getDistance($lat, $lon, $coord['latitude'], $coord['longitude']);
			// echo $core_poi_uuid . ' - ' . $dist . '  ';
			if ($dist > $radius)
				continue;
				
			$result[$core_poi_uuid] = $dist;
		}

		if ($distance_ordered)
			asort($result);
		
		return array_keys($result);
	}
	
	protected function query_bbox($west, $east, $south, $north) {
		$bbox_query = array(
			'location.wgs84.longitude' => array('$gt' => $west, '$lt' => $east),
			'location.wgs84.latitude' => array('$gt' => $south, '$lt' => $north)
		);
		$spatialresult = $this->core->find($bbox_query, array("_id" => 1, 'location.wgs84.longitude' => 1, 'location.wgs84.latitude' => 1));
		return $spatialresult;
	}
	
	protected function getDistance($lat1, $lon1, $lat2, $lon2) {
		// distance in meters
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = abs(round(rad2deg(acos($dist)) * 60.0 * 1.1515 * 1.609344 * 1000.0, 2));
		return $dist;
	}
	
}

SpatialIndex::register('mongo-naive', 'MongoNaive');

?>