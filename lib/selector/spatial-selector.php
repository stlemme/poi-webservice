<?php

require_once(__DIR__ . '/../poi-selector.php');


abstract class SpatialSelector extends POISelector
{
	protected $spatialIndex;
	protected $result;
	
	protected function __construct($spatialIndex) {
		$this->spatialIndex = $spatialIndex;
	}
	
	public function result() {
		return $this->result;
	}
}

?>