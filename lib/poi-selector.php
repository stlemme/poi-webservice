<?php

abstract class POISelector
{
	abstract public function parameters();
	public function optional() {
		return array();
	}
	
	abstract public function setup($params, $defaults);
	abstract public function result();
}

?>