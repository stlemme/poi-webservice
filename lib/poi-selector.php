<?php


abstract class POISelector implements Iterator
{
	abstract public function rewind();
	abstract public function current();
	abstract public function key();
	abstract public function next();
	abstract public function valid();
	
	abstract public function parameters()
}


class SpatialSelector extends POISelector
{
}


?>