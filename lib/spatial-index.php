<?php

class SpatialIndex
{
	// TODO: query methods should return an iterator and not limiting the result set!
	public function bbox_search($west, $east, $south, $north) {
		return array();
	}
	
	public function radial_search($lat, $lon, $radius, $distance_ordered) {
		return array();
	}
	
	public function set($poi_uuid, &$poi_data) {
	}
	
	public function remove($poi_uuids) {
	}

	
	///////////////////////////////////////////////////////////////////////////

	
	public static function create($idx, $db) {
		if (!isset(self::$types[$idx]))
			return;
			
		return new self::$types[$idx]($db);
	}
	
	public static function register($idx, $type) {
		self::$types[$idx] = $type;
	}
	
	private static $types = array();
}


?>