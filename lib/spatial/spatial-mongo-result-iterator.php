<?php

class SpatialMongoResultIterator implements Iterator
{
	private $mongoIterator = null;
	private $core_comp;
	
	public function __construct($core_comp, $mongoResult) {
		$this->mongoIterator = $mongoResult;
		$this->core_comp = $core_comp;
	}
	
	public function rewind() {
		if ($this->mongoIterator == null)
			return;
		
		try {
			$this->mongoIterator->rewind();
		} catch (MongoCursorException $e) {
			$this->mongoIterator = null;
		}
	}
	
	public function current() {
		$current = $this->mongoIterator->current();
		unset($current['_id']);
		return array($this->core_comp => $current);
	}
	
	public function key() {
		return $this->mongoIterator->key();
	}
	
	public function next() {
		$this->mongoIterator->next();
	}
	
	public function valid() {
		if ($this->mongoIterator == null)
			return false;
		return $this->mongoIterator->valid();
	}
}

?>