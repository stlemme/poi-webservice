<?php

require_once(__DIR__ . '/../lib/poi-data-provider.php');
require_once(__DIR__ . '/../lib/response.php');
require_once(__DIR__ . '/csv-importer.php');


$dp = POIDataProvider::getInstance();

$import_file = "DE.txt";

$fieldnames = array(
	"geonameid",
	"name",
	"asciiname",
	"alternatenames",
	"latitude",
	"longitude",
	"feature_class",
	"feature_code",
	"country_code",
	"cc2",
	"admin1_code",
	"admin2_code",
	"admin3_code",
	"admin4_code",
	"population",
	"elevation",
	"dem",
	"timezone",
	"modification_date"
);

$importer = new CsvImporter($import_file, false, $fieldnames, "\t", 0);

while(($row = $importer->get()) != null) {
	
	$poi_data = array(
	
		"fw_core" => array(
			"category" => $row["feature_code"],
			
			"name" => array(
				"" => $row["name"]
			),
			
			"url" => array(
				"" => "http://www.geonames.org/" . $row["geonameid"]
			),
			
			"label" => array(
				"" => $row["name"]
			),
			
			"location" => array( 
				"wgs84" => array(
					"latitude" => floatval($row["latitude"]),
					"longitude" => floatval($row["longitude"])
				)
			),
			
			// "description": {
			// },
			
			"source" => array(
				"name" => "geonames.org",
				"website" => "http://www.geonames.org/",
				"license" => "http://creativecommons.org/licenses/by/3.0/"
			),
			
			"last_update" => array(
				"timestamp" => $row["modification_date"]
			)
			
		),
		
		// TODO: set more than only fw_core component
	);

	$poi_info = $dp->create($poi_data);
	
	$pois[] = $poi_info['uuid'];

}

Response::json(array("imported_pois" => $pois));

?>