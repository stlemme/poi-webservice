<?php

require_once(__DIR__ . '/../poi-selector.php');


class IdSelector extends POISelector
{
	private $i;
	private $ids;
	
	public function __construct($ids) {
		$this->ids = $ids;
		$this->i = 0;
	}
	
	public function rewind() {
		$this->i = 0;
	}
	
	public function current() {
		return array();
	}
	
	public function key() {
		return $this->ids[$this->i];
	}
	
	public function next() {
		$this->i++;
	}
	
	public function valid() {
		return $this->i < count($this->ids);
	}
}


?>