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
			$this->db = $this->mongo->selectDB(Utils::jsonPath($db_config, 'name'));
		} catch (MongoConnectionException $e) {
			Response::fail(500, "Error connecting to MongoDB server");
		}
	}

	public function getComponent($uuid, $comp_name)
	{
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
		// TODO: implement
	}
	
	public function removeComponent($comp_name, $uuids)
	{
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