<?php

require_once(__DIR__ . '/attribute-filter.php');


abstract class HasAttributeFilter extends AttributeFilter
{
	private $filterParameterName;
	
	public function __construct($attribute, $filterParameter) {
		parent::__construct($attribute);
		$this->filterParameterName = $filterParameter;
	}

	public function parameters() {
		if ($this->filterParameterName != null) {
			return array(
				$this->filterParameterName => array(
					'type' => 'bool'
				)
			);
		} else {
			return array();
		}
	}
	
	public function active($params) {
		if ($this->filterParameterName === null)
			return true;
		
		if (!isset($params[$this->filterParameterName]))
			return false;
		
		return $params[$this->filterParameterName];
	}
	
	public function match($poi_data) {
		$val = $this->value($poi_data);
		return ($val !== null);
	}
}

?>