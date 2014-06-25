<?php

require_once(__DIR__ . '/attribute-filter.php');


class CategoryFilter extends AttributeFilter
{
	public function __construct($category) {
		parent::__construct('fw_core.category');
		$this->category = $category;
	}
	
	public function match($poi_data) {
		$val = $this->value($poi_data);
		
		if ($val == null)
			return false;
		
		return in_array($val, $this->category, true);
	}
}

?>