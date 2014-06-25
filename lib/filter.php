<?php

interface Filter
{
	public function requiredComponents();
	public function match($poi_data);
}

?>