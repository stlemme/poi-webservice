<?php

require_once(__DIR__ . '/utils.php');


interface Database
{
	public function connect($db_config);
	public function getComponent($uuid, $comp_name);
	public function storeComponent($uuid, $comp_name, $comp_data);
	public function removeComponent($comp_name, $uuids);
}


class MongoDatabase implements Database
{
	private $mongo = null;
	private $db = null;

	public function connect($db_config)
	{
		ini_set("mongo.allow_empty_keys", 1);
		
		try {
			$this->mongo = new MongoClient();
			$dbname = Utils::json_path($db_config, 'name');
			if ($dbname === null)
				return false;
			$this->db = $this->mongo->selectDB($dbname);
			return true;
		} catch (MongoConnectionException $e) {
			$this->db = null;
			return false;
		}
	}

	public function getComponent($uuid, $comp_name)
	{
		if ($this->db === null)
			return null;
		
		try {
			$collection = $this->db->selectCollection($comp_name);
			$comp_data = $collection->findOne(array("_id" => $uuid), array("_id" => false));
			return $comp_data;
		} catch (MongoException $e) {
			// Response::fail(500, "Error querying MongoDB server");
			return null;
		}
	}
	
	public function storeComponent($uuid, $comp_name, $comp_data)
	{
		if ($this->db === null)
			return false;
		// TODO: implement
	}
	
	public function removeComponent($comp_name, $uuids)
	{
		if ($this->db === null)
			return false;
	
		try {
			$collection = $this->db->selectCollection($comp_name);
			$collection->remove(array('_id' => array('$in' => $uuids)));
			return true;
		} catch (MongoException $e) {
			return false;
		}
	}
	
	public function manualQuery($collection, $query, $projection = null)
	{
		if ($this->db === null)
			return null;

		try {
			$c = $this->db->selectCollection($collection);
			if ($projection == null) {
				return $c->find($query);
			} else {
				return $c->find($query, $projection);
			}
		} catch (MongoException $e) {
			Response::fail(500, $e);
		}

	}
	
	public function ensureIndex($collection, $index)
	{
		if ($this->db === null)
			return false;
		
		try {
			$c = $this->db->selectCollection($collection);
			$c->ensureIndex($index);
			return true;
		} catch (MongoConnectionException $e) {
			return false;
		}
	}

}

?>