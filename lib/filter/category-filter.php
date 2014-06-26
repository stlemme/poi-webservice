<?php

require_once(__DIR__ . '/attribute-filter.php');


class CategoryFilter extends AttributeFilter
{
	private $filterParameterName = 'category';
	private $category = null;
	
	public function __construct() {
		parent::__construct('fw_core.category');
	}
	
	public function parameters() {
		return array(
			$this->filterParameterName => array(
				'type' => 'string',
				'array' => true
			)
		);
	}
	
	public function active($params) {
		if (!isset($params[$this->filterParameterName]))
			return false;
			
		$this->category = $params[$this->filterParameterName];
		return true;
	}
	
	public function match($poi_data) {
		$val = $this->value($poi_data);
		
		if ($val == null)
			return false;
		
		return in_array($val, $this->category, true);
	}
}

?>