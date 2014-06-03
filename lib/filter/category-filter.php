<?php

require_once(__DIR__ . '/../filter.php');


class CategoryFilter
{
	private $category;
	
	public function __construct($category) {
		$this->category = $category;
	}
	
	public function requiredComponents() {
		return array('fw_core');
	}
	
	public function match($poi_data) {
		if (!isset($poi_data['fw_core']))
			return false;
			
		if (!isset($poi_data['fw_core']['category']))
			return false;
		
		return in_array($poi_data['fw_core']['category'], $this->category, true);
	}
}

?>