<?php

require_once(__DIR__ . '/spatial-selector.php');


class BBoxSelector extends SpatialSelector
{
	public function __construct($spatialIndex) {
		parent::__construct($spatialIndex);
	}
	
	public function parameters() {
		return array(
			'north' => array(
				'type' => 'float',
				'min' => -90.0,
				'max' => 90.0
			),

			'south' => array(
				'type' => 'float',
				'min' => -90.0,
				'max' => 90.0
			),
			
			'west' => array(
				'type' => 'float',
				'min' => -180.0,
				'max' => 180.0
			),
			
			'east' => array(
				'type' => 'float',
				'min' => -180.0,
				'max' => 180.0
			)
		);
	}

	public function setup($params, $defaults)
	{
		$north = $params['north'];
		$south = $params['south'];
		$west  = $params['west'];
		$east  = $params['east'];

		$this->result = $this->spatialIndex->bbox_search($west, $east, $south, $north);
	}
}

?>