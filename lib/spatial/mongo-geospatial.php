<?php

require_once(__DIR__ . '/../spatial-index.php');
require_once(__DIR__ . '/spatial-mongo-result-iterator.php');


class MongoGeospatial extends SpatialIndex
{
	private $core_comp = 'fw_core';
	
	public function __construct($mongodb) {
		$this->mongodb = $mongodb;
		$this->core = $this->mongodb->selectCollection($this->core_comp);
		$this->core->ensureIndex(array('location.wgs84' => '2dsphere'));
		$this->core->ensureIndex(array('location.wgs84' => '2d'));
	}
	
	public function set($poi_uuid, &$poi_data) {
		// ensure fw_core.location.wgs84 longitude/latitude order for geospatial index of mongodb
		// TODO: check with json schema existence of components
		if (!isset($poi_data['fw_core']))
			return;
		
		$loc = $poi_data['fw_core']['location'];
		$poi_data['fw_core']['location']['wgs84'] = array(
			'longitude' => $loc['wgs84']['longitude'],
			'latitude' => $loc['wgs84']['latitude']
		);
	}
	
	public function bbox_search($west, $east, $south, $north) {
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
		// TODO: refactor mongodb as class to encapsulate exception handling
		$spatialresult = null;
		try {
			$spatialresult = $this->core->find($bbox_query, array("_id" => 1));
		} catch (MongoException $e) {
			Response::fail(500, $e);
		}
		return new SpatialMongoResultIterator($spatialresult);
	}

	public function radial_search($lat, $lon, $radius, $distance_ordered) {
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
		$spatialresult = null;
		try {
			$spatialresult = $this->core->find($radial_query, array("_id" => 1));
		} catch (MongoException $e) {
			Response::fail(500, $e);
		}
		return new SpatialMongoResultIterator($spatialresult);
	}
	
}

SpatialIndex::register('mongo-geospatial', 'MongoGeospatial');

?>