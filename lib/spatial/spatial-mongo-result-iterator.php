<?php

class SpatialMongoResultIterator implements Iterator
{
	private $mongoIterator = null;
	private $i = 0;
	
	public function __construct($mongoResult) {
		$this->mongoIterator = $mongoResult;
	}
	
	public function rewind() {
		if ($this->mongoIterator == null)
			return;
		
		try {
			$this->mongoIterator->rewind();
			$this->i = 0;
		} catch (MongoCursorException $e) {
			$this->mongoIterator = null;
		}
	}
	
	public function current() {
		return $this->mongoIterator->key();
	}
	
	public function key() {
		return $this->i;
	}
	
	public function next() {
		$this->mongoIterator->next();
		$this->i++;
	}
	
	public function valid() {
		if ($this->mongoIterator == null)
			return false;
		return $this->mongoIterator->valid();
	}
}


?>
