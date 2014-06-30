<?php

require_once(__DIR__ . '/../filter.php');
require_once(__DIR__ . '/../utils.php');


abstract class AttributeFilter extends Filter
{
	private $attribute;
	private $comp;

	
	public function requiredComponents() {
		return array($this->comp);
	}
	

	protected function __construct($attribute) {
		$this->attribute = $attribute;
		$path = explode('.', $this->attribute, 2);
		$this->comp = array_shift($path);
	}

	protected function value($poi_data) {
		return Utils::json_path($poi_data, $this->attribute);
	}
}

?>