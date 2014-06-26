<?php

require_once(__DIR__ . '/spatial-selector.php');


class RadialSelector extends SpatialSelector
{
	public function __construct($spatialIndex) {
		parent::__construct($spatialIndex);
	}
	
	public function parameters() {
		return array(
			'lat' => array(
				'type' => 'float',
				'min' => -90.0,
				'max' => 90.0
			),

			'lon' => array(
				'type' => 'float',
				'min' => -180.0,
				'max' => 180.0
			)
		);
	}
			
	public function optional() {
		return array(
			'radius' => array(
				'type' => 'float',
				'min' => 0.0
			)
		);
	}

	public function setup($params, $defaults)
	{
		$radius = isset($params['radius']) ? $params['radius'] : $defaults['radius'];

		$lon = $params['lon'];
		$lat = $params['lat'];

		$this->result = $this->spatialIndex->radial_search($lat, $lon, $radius, false);
	}
}

?>