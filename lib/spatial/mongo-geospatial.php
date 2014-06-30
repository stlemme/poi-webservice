<?php

require_once(__DIR__ . '/../spatial-index.php');
require_once(__DIR__ . '/../utils.php');
require_once(__DIR__ . '/spatial-mongo-result-iterator.php');


class MongoGeospatial extends SpatialIndex
{
	private $core_comp = 'fw_core';
	private $mongodb;
	
	public function __construct($mongodb) {
		$this->mongodb = $mongodb;
		$this->mongodb->ensureIndex($this->core_comp, array('location.wgs84' => '2dsphere'));
		$this->mongodb->ensureIndex($this->core_comp, array('location.wgs84' => '2d'));
	}
	
	public function set($poi_uuid, &$poi_data)
	{
		$loc = Utils::jsonPath($poi_data, 'fw_core.location.wgs84');

		if ($loc == null)
			return;
		
		// TODO: use somekind of Utils::setJsonPath($poi_data, 'fw_core.location.wgs84', array( ... ))
		// TODO: handle elevation/altitude data
		$poi_data['fw_core']['location']['wgs84'] = array(
			'longitude' => $loc['longitude'],
			'latitude' => $loc['latitude']
		);
	}
	
	public function bbox_search($west, $east, $south, $north)
	{
		$bbox_query = array(
			'location.wgs84' => array(
				'$geoWithin' => array(
					'$box' => array(
						array($west, $south),
						array($east, $north)
					)
				)
			)
		);
		$spatialresult = $this->mongodb->manualQuery($this->core_comp, $bbox_query);
		return new SpatialMongoResultIterator($this->core_comp, $spatialresult);
	}

	public function radial_search($lat, $lon, $radius, $distance_ordered)
	{
		// TODO: sort pois with regard to their distance (if desired)
		$radians = $radius / 6371000.0;
		$radial_query = array(
			'location.wgs84' => array(
				'$geoWithin' => array(
					'$centerSphere' => array(
						array($lon, $lat),
						$radians
					)
				)
			)
		);
		$spatialresult = $this->mongodb->manualQuery($this->core_comp, $radial_query);
		return new SpatialMongoResultIterator($this->core_comp, $spatialresult);
	}
	
}

?>