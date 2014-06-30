<?php

abstract class Filter
{
	abstract public function parameters();
	abstract public function active($params);
	
	abstract public function requiredComponents();
	abstract public function match($poi_data);

	
	///////////////////////////////////////////////////////////////////////////

	
	public static function create($filter) {
		$filterClass = Utils::loadClassFromFile($filter, __DIR__ . '/filter');
		if ($filterClass == null)
			return null;
		
		return new $filterClass();
	}
}

?>