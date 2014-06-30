<?php

require_once(__DIR__ . '/attribute-filter.php');


class CategoryFilter extends AttributeFilter
{
	const FilterParameterName = 'category';
	const AttributeName = 'fw_core.category';
	private $category = null;
	
	public function __construct() {
		parent::__construct(self::AttributeName);
	}
	
	public function parameters() {
		return array(
			self::FilterParameterName => array(
				'type' => 'string',
				'array' => true
			)
		);
	}
	
	public function active($params) {
		if (!isset($params[self::FilterParameterName]))
			return false;
			
		$this->category = $params[self::FilterParameterName];
		return true;
	}
	
	public function match($poi_data) {
		$val = $this->value($poi_data);
		
		if ($val === null)
			return false;
		
		return in_array($val, $this->category, true);
	}
}

?>