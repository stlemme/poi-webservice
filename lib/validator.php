<?php

// error_reporting(E_ALL);


require_once(__DIR__ . '/../jsv4-php/jsv4.php');
require_once(__DIR__ . '/../jsv4-php/schema-store.php');
require_once(__DIR__ . '/utils.php');


class Validator
{
	private $store;
	private $schema;
	private $errors;

	public function __construct($schemaPath, $components, $schemaBaseUrl)
	{
		$this->store = new SchemaStore();
		
		$schemaComponents = array();
		
		// load component schemas
		foreach ($components as $component)
		{
			$schemaFilename = $schemaPath . '/components/' . $component . '.json';
			$schemaUrl = $schemaBaseUrl . $component . '.json';
			
			// print_r($schemaUrl);
			$schema = Utils::json_decode(file_get_contents($schemaFilename), true, true);
			$this->store->add($schemaUrl, $schema);
			
			$schemaComponents[$component] = array(
				'$ref' => $schemaUrl
			);
		}

		// load utility type schemas
		foreach (glob($schemaPath . '/utils/*.json') as $filename) {
			$utilType = basename($filename, '.json');

			$schemaFilename = $schemaPath . '/utils/' . $utilType . '.json';
			$schemaUrl = $schemaBaseUrl . $utilType . '.json';

			// print_r($schemaUrl);
			$schema = Utils::json_decode(file_get_contents($schemaFilename), true, true);
			$this->store->add($schemaUrl, $schema);
		}


		// load main poi schema
		$schemaUrl = $schemaBaseUrl . 'poi' . '.json';
		$this->store->add($schemaUrl, array(
			'title' => 'Point of Interest',
			'type' => 'object',
			'properties' => $schemaComponents,
			'additionalProperties' => false
		));
		
		
		// print_r($this->store->missing());
		
		// get resolved main poi schema for validation
		// $this->schema = $this->store->get($schemaUrl);
		print_r($this->schema);
	}
	
	public function validate($data)
	{
		$this->errors = null;
		
		$valres = Jsv4::validate($data, $this->schema);

		if ($valres->valid)
			return true;

		$this->errors = $valres->errors;
		return false;
	}
	
	public function getErrors() {
		return $this->errors;
	}

}

?>
