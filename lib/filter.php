<?php

interface Filter
{
	public function parameters();
	public function active($params);
	
	public function requiredComponents();
	public function match($poi_data);
}

?>