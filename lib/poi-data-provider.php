<?php

require_once(__DIR__ . '/request.php');
require_once(__DIR__ . '/response.php');
require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/validator.php');
require_once(__DIR__ . '/spatial-index.php');
require_once(__DIR__ . '/filter.php');
require_once(__DIR__ . '/database.php');


class POIDataProvider
{
	private $db = null;
	private $spatialIndex = null;
	private $components = array();
	private $filters = array();
	private $filterParams = array();
	private $config;
	
	private $schemaPath;
	private $validator;
	
	const ConfigFile = 'config.json';
	
	
	///////////////////////////////////////////////////////////////////////////
	

	private function loadComponents()
	{
		foreach (glob($this->schemaPath . '/components/*_*.json') as $filename) {
			$this->components[] = basename($filename, '.json');
		}
	}
	
	private function loadConfig($configFile)
	{
		if (file_exists($configFile)) {
			$config_content = file_get_contents($configFile);
			$this->config = Utils::json_decode($config_content);
			if ($this->config === null)
				Response::fail(500, "Invalid configuration file.");
		} else {
			$this->config = null;
		}
	}
	
	private function loadSpatialIndex()
	{
		$idxType = $this->config('spatial_index');
		if ($idxType == null)
			return;
		
		$this->spatialIndex = SpatialIndex::create($idxType, $this->db);
	}

	private function loadFilters()
	{
		$this->filters = array();
		$this->filterParams = array();
		
		$filterConfig = $this->config('filters');
		if ($filterConfig == null)
			return;

		foreach ($filterConfig as $fname => $factive)
		{
			if (!$factive)
				continue;
			
			$filter = Filter::create($fname);

			if ($filter == null)
				continue;
			
			$this->addFilter($filter);
		}
	}

	private function commonParameters() {
		return array(
			'component' => array(
				'type' => 'enum',
				'array' => true,
				'values' => $this->getSupportedComponents()
			),
			
			'max_results' => array(
				'type' => 'int',
				'min' => 1,
				'max' => $this->config('query_defaults.max_results')
			),
			
			'jsoncallback' => array(
				'type' => 'string'
			)
		);
	}
	
	private function filterParameters() {
		return $this->filterParams;
	}

	
	///////////////////////////////////////////////////////////////////////////

	
	public function __construct()
	{
		$this->loadConfig(self::configFile());
		
		// TODO: use database factory
		$this->db = new MongoDatabase();
		// TODO: how to handle errors at this stage
		$this->db->connect($this->config('db'));
			// Response::fail(500, "Error connecting to MongoDB server");

		$this->schemaPath = realpath(__DIR__ . '/../' . $this->config('schema.path'));
		$this->loadComponents();
		
		$this->loadSpatialIndex();
		
		$this->loadFilters();

		$this->validator = new Validator(
			$this->schemaPath,
			$this->components,
			$this->config('schema.baseurl')
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
	
	public function query($selector, $queryParams)
	{
		$req = new Request(array(
			'required' => $selector->parameters(),
			'optional' => array_merge($this->filterParameters(), $selector->optional(), $this->commonParameters())
		));

		$req->checkMethod('GET', "You must use HTTP GET for retrieving POIs!");

		$params = $req->parseParams($queryParams);
		$components = isset($params['component']) ? $params['component'] : $this->getSupportedComponents();
		$max_results = isset($params['max_results']) ? $params['max_results'] : $this->config('query_defaults.max_results');
		// TODO: handle jsoncallback parameter

		$selector->setup($params, $this->config('query_defaults'));
		
		$filters = array();
		
		foreach($this->filters as $f)
		{
			if ($f->active($params))
				$filters[] = $f;
		}

		$pois = array();
		$results = 0;
		
		$result = $selector->result();
		// print_r(iterator_to_array($result));

		foreach($result as $poi_uuid => $poi_data)
		{
			if ($results >= $max_results)
				break;
					
			foreach($filters as $f)
			{
				if (!$this->applyFilter($poi_uuid, $poi_data, $f)) {
					$poi_uuid = null;
					break;
				}
			}
			
			if ($poi_uuid == null)
				continue;

			$this->complete($poi_uuid, $poi_data, $components);
			$pois[$poi_uuid] = $poi_data;
			
			$results++;
		}
		
		return $pois;
	}
	
	public function update($uuid, $poi_data)
	{
		foreach($poi_data as $comp_name => &$comp_data) 
		{
			$old_comp_data = $this->read($uuid, $comp_name);
			if ($old_comp_data == null)
				continue;
			
			$comp_data = Utils::json_update($old_comp_data, $comp_data);
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
			$this->db->removeComponent($component, $uuids);
		
		if ($this->spatialIndex != null) {
			$this->spatialIndex->remove($uuids);
		}

	}
	
	///////////////////////////////////////////////////////////////////////////

	public static function configFile() {
		$file = __DIR__ . '/../' . self::ConfigFile;
		$filePath = realpath($file);
		if ($filePath !== false)
			return $filePath;
		return $file;
	}
	
	public function getSupportedComponents() {
		return $this->components;
	}

	public function config($key) {
		return Utils::json_path($this->config, $key);
	}
	
	public function getSpatialIndex() {
		return $this->spatialIndex;
	}
	
	public function addFilter($filter)
	{
		$this->filters[] = $filter;
		$this->filterParams = array_merge($this->filterParams, $filter->parameters());
	}
	
	private function applyFilter($poi_uuid, &$poi_data, $filter)
	{
		foreach ($filter->requiredComponents() as $comp_name)
		{
			if (isset($poi_data[$comp_name]))
				continue;
			
			$comp_data = $this->db->getComponent($poi_uuid, $comp_name);
			if ($comp_data === null)
				continue;
			
			$poi_data[$comp_name] = $comp_data;
		}
		
		return $filter->match($poi_data);
	}
	
	private function complete($poi_uuid, &$poi_data, $components)
	{
		foreach ($components as $comp_name)
		{
			if (isset($poi_data[$comp_name]))
				continue;
			
			$comp_data = $this->db->getComponent($poi_uuid, $comp_name);
			if ($comp_data == null)
				continue;

			$poi_data[$comp_name] = $comp_data;
		}
	}
	
	///////////////////////////////////////////////////////////////////////////

	// private static $instance;
	
	// public static function getInstance() {
		// if(!self::$instance) {
			// self::$instance = new self();
		// } 
		// return self::$instance; 
	// }

}

?>