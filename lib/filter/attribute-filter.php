<?php

require_once(__DIR__ . '/../filter.php');
require_once(__DIR__ . '/../utils.php');


abstract class AttributeFilter implements Filter
{
	private $attribute;
	private $comp;

	
	abstract public function parameters();
	abstract public function active($params);
	
	public function requiredComponents() {
		return array($this->comp);
	}
	
	abstract public function match($poi_data);


	protected function __construct($attribute) {
		$this->attribute = $attribute;
		$path = explode('.', $this->attribute, 2);
		$this->comp = array_shift($path);
	}

	protected function value($poi_data) {
		return Utils::jsonPath($poi_data, $this->attribute);
	}
}

?>