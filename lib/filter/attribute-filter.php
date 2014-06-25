<?php

require_once(__DIR__ . '/../filter.php');
require_once(__DIR__ . '/../utils.php');


class AttributeFilter implements Filter
{
	private $attribute;
	private $comp;
	
	protected function __construct($attribute) {
		$this->attribute = $attribute;
		$path = explode('.', $this->attribute, 2);
		$this->comp = array_shift($path);
	}
	
	public function requiredComponents() {
		return array($this->comp);
	}
	
	protected function value($poi_data) {
		return Utils::jsonPath($poi_data, $this->attribute);
	}
	
	public function match($poi_data) {
		return false;
	}
}

?>