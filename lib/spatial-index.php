<?php

require_once(__DIR__ . '/utils.php');


abstract class SpatialIndex
{
	abstract public function bbox_search($west, $east, $south, $north);
	abstract public function radial_search($lat, $lon, $radius, $distance_ordered);
	
	public function set($poi_uuid, &$poi_data) {
	}
	
	public function remove($poi_uuids) {
	}

	
	///////////////////////////////////////////////////////////////////////////

	
	public static function create($idx, $db) {
		$idxClass = Utils::loadClassFromFile($idx, __DIR__ . '/spatial');
		if ($idxClass == null)
			return;
			
		return new $idxClass($db);
	}
}

?>