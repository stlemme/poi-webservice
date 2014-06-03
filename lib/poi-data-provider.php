<?php

require_once(__DIR__ . '/request.php');
require_once(__DIR__ . '/response.php');
require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/validator.php');
require_once(__DIR__ . '/spatial-index.php');


class POIDataProvider
{
	private $db = null;
	private $spatialIndex = null;
	private $components = array();
	private $schemaPath;
	private $validator;
	
	private $config = array(
		'db_name' => 'poi_db4',
		'schema_baseurl' => 'http://example.com/poi-v1/',
		
		'max_results' => 10000,
		
		'spatial_index' => 'mongo-geospatial',
		
		'default_radius' => 300
	);
	
	
	///////////////////////////////////////////////////////////////////////////
	
	
	private function connectMongoDB($db_name)
	{
		ini_set("mongo.allow_empty_keys", 1);
		
		try {
			$this->mongo = new MongoClient();
			$this->db = $this->mongo->selectDB($db_name);
		} catch (MongoConnectionException $e) {
			Response::fail(500, "Error connecting to MongoDB server");
		}
	}

	private function getComponent($component_name, $uuid)
	{
		try {
			$collection = $this->db->selectCollection($component_name);
			$component = $collection->findOne(array("_id" => $uuid), array("_id" => false));
			return $component;
		} catch (MongoConnectionException $e) {
			// Response::fail(500, "Error querying MongoDB server");
			return null;
		}
	}
	
	private function storeComponent($comp_name, $comp_data, $uuid)
	{
		// TODO: implement
	}

	private function loadComponents()
	{
		foreach (glob($this->schemaPath . '/components/*_*.json') as $filename) {
			$this->components[] = basename($filename, '.json');
		}
	}
	
	private function loadSpatialIndex()
	{
		if (!isset($this->config['spatial_index']))
			return;
			
		$idxType = $this->config['spatial_index'];
		
		include(realpath(__DIR__ . '/spatial/' . $idxType . '.php'));
		
		$this->spatialIndex = SpatialIndex::create($idxType, $this->db);
	}

	private function getCommonParams() {
		return array(
			'poi_id' => array(
				'type' => 'uuid',
				'array' => true
			),
			
			'component' => array(
				'type' => 'enum',
				'array' => true,
				'values' => $this->getSupportedComponents()
			),
			
			'category' => array(
				'type' => 'string',
				'array' => true
			),
			
			'max_results' => array(
				'type' => 'int',
				'min' => 1,
				'max' => $this->config['max_results']
			),
			
			'jsoncallback' => array(
				'type' => 'string'
			),
			
			// bbox_search
			'north' => array(
				'type' => 'float',
				'min' => -90.0,
				'max' => 90.0
			),

			'south' => array(
				'type' => 'float',
				'min' => -90.0,
				'max' => 90.0
			),
			
			'west' => array(
				'type' => 'float',
				'min' => -180.0,
				'max' => 180.0
			),
			
			'east' => array(
				'type' => 'float',
				'min' => -180.0,
				'max' => 180.0
			),

			// radial_search
			'lat' => array(
				'type' => 'float',
				'min' => -90.0,
				'max' => 90.0
			),

			'lon' => array(
				'type' => 'float',
				'min' => -180.0,
				'max' => 180.0
			),
			
			'radius' => array(
				'type' => 'float',
				'min' => 0.0
			),
			
			
			'begin_time' => array(),
			'end_time' => array()
		);
	}

	
	///////////////////////////////////////////////////////////////////////////

	
	private function __construct()
	{
		$this->connectMongoDB($this->config["db_name"]);

		$this->schemaPath = realpath(__DIR__ . "/../schema");
		$this->loadComponents();
		
		$this->loadSpatialIndex();

		$this->validator = new Validator(
			$this->schemaPath,
			$this->components,
			$this->config["schema_baseurl"]
		);
	}
	
	
	public function create($poi_data)
	{
		// validate poi data against json schema
		if (!$this->validator->validate($poi_data)) {
			// print_r($this->validator->getErrors());
			return null;
		}

		$uuid = Utils::generate_guidv4();
		$timestamp = time();
		// TODO: set update_stamp to $timestamp
		
		// add POI location (if any) to spatial index
		if ($this->spatialIndex != null) {
			$this->spatialIndex->set($uuid, $poi_data);
		}
		
		
		foreach($poi_data as $comp_name => $comp_data) 
		{
			// TODO: how to handle unsupported or validation failed components
			if (!in_array($comp_name, $this->components))
				// TODO: this will never happen since the validation would fail before
				continue;

			$comp_data["_id"] = $uuid;
			
			try {
				$collection = $this->db->$comp_name;
				$collection->insert($comp_data);
			} catch (MongoException $e) {
				Response::fail(500, $e);
			}
		}
		

		$new_poi_info = array(
			'uuid' => $uuid,
			'timestamp' => $timestamp
		);
		
		return $new_poi_info;
	}
	
	public function read($uuid, $components)
	{
		$data = array();
		
		// TODO: check if one query per component is faster
		foreach ($components as $component)
		{
			$comp_data = $this->getComponent($component, $uuid);
			if ($comp_data == null)
				continue;

			$data[$component] = $comp_data;
		}
		return $data;
	}
	
	public function update($uuid, $poi_data)
	{
		foreach($poi_data as $comp_name => &$comp_data) 
		{
			$old_comp_data = $this->read($uuid, $comp_name);
			if ($old_comp_data == null)
				continue;
			$comp_data = array_replace_recursive($old_comp_data, $comp_data);
			// TODO: remove "deleted" (null) fields
		}
	
		if (!$this->validator->validate($poi_data)) {
			// print_r($this->validator->getErrors());
			return false;
		}

		$timestamp = time();
		// TODO: set update_stamp to $timestamp

        // $update_timestamp = 0;
        
        // if (isset($comp_data['last_update']))
        // {
            // $last_update = $comp_data['last_update'];
            // if (isset($last_update['timestamp']))
            // {
                // $update_timestamp = intval($last_update['timestamp']);
            // }
        // }
        
        // if ($update_timestamp == 0)
        // {
            // header("HTTP/1.0 400 Bad Request");
            // die("No valid 'last_update:timestamp' value was found for '$comp_name' component!");
        // }
        
        
        // if (isset($existing_component['last_update']))
        // {
            // if (isset($existing_component['last_update']['timestamp']))
            // {
                // $curr_timestamp = $existing_component['last_update']['timestamp'];
                // if ($curr_timestamp != $update_timestamp) {
                    // header("HTTP/1.0 400 Bad Request");
                    // die("The given last_update:timestamp (". $update_timestamp .") does not match the value in the database (". $curr_timestamp .") in fw_core!");
                // }
            // }   
        // }
		// }
		
		// TODO: Luo/päivitä timestamp arvo!
		
		// if (!isset($comp_data['last_update']))
		// {
			// $comp_data['last_update'] = array();
		// }
		// $comp_data['last_update']['timestamp'] = time();
    
		// update POI location (if any) in spatial index
		if ($this->spatialIndex != null) {
			$this->spatialIndex->set($uuid, $poi_data);
		}
 		
		foreach($poi_data as $comp_name => $comp_data) 
		{
			// TODO: how to handle unsupported or validation failed components
			if (!in_array($comp_name, $this->components))
				// TODO: this will never happen since the validation would fail before
				continue;

			$comp_data["_id"] = $uuid;
			
			try {
				$collection = $this->db->selectCollection($comp_name);
				$collection->update(array("_id" => $uuid), $comp_data, array("upsert" => true));
			} catch (MongoException $e) {
				Response::fail(500, $e);
			}
		}
		
		return true;
	}
	
	public function delete($uuids)
	{
		foreach($this->components as $component)
		{
			$collection = $this->db->selectCollection($component);
			$collection->remove(array('_id' => array('$in' => $uuids)));
		}
		
		if ($this->spatialIndex != null) {
			$this->spatialIndex->remove($uuids);
		}

	}
	
	///////////////////////////////////////////////////////////////////////////

	public function getSupportedComponents() {
		return $this->components;
	}

	public function request($req_params = array(), $opt_params = array()) {
		return new Request(array(
			'params' => $this->getCommonParams(),
			'required' => $req_params,
			'optional' => $opt_params
		));
	}
	
	public function config($key) {
		if (!isset($this->config[$key]))
			return;
			
		return $this->config[$key];
	}
	
	public function getSpatialIndex() {
		return $this->spatialIndex;
	}
	
	public function applyFilter($poi_uuid, &$poi_data, $filter)
	{
		foreach ($filter->requiredComponents() as $comp_name) {
			if (isset($poi_data[$comp_name]))
				continue;
			
			$comp_data = $this->getComponent($comp_name, $poi_uuid);
			if ($comp_data == null)
				return false;
				
			$poi_data[$comp_name] = $comp_data;
		}
		
		return $filter->match($poi_data);
	}
	
	public function complete($poi_uuid, &$poi_data, $components)
	{
		foreach ($components as $comp_name)
		{
			if (isset($poi_data[$comp_name]))
				continue;
			
			$comp_data = $this->getComponent($comp_name, $poi_uuid);
			if ($comp_data == null)
				continue;

			$poi_data[$comp_name] = $comp_data;
		}
	}
	
	///////////////////////////////////////////////////////////////////////////

	private static $instance;
	
	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new self();
		} 
		return self::$instance; 
	}

}

?>