<?php

require_once(__DIR__ . '/../poi-selector.php');


class IdSelectorResultIterator implements Iterator
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

class IdSelector extends POISelector
{
	private $result;
	
	public function __construct() {
		$this->result = null;
	}
	
	public function parameters() {
		return array(
			'poi_id' => array(
				'type' => 'uuid',
				'array' => true
			),
		);
	}
	
	public function setup($params, $defaults) {
		if (!isset($params['poi_id']))
			return;
		$this->result = new IdSelectorResultIterator($params['poi_id']);
	}
	
	public function result() {
		return $this->result;
	}
}


?>