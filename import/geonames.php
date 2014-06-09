<?php

require_once(__DIR__ . '/../lib/poi-data-provider.php');
require_once(__DIR__ . '/../lib/response.php');
require_once(__DIR__ . '/csv-importer.php');


set_time_limit(300);


$dp = POIDataProvider::getInstance();

$req = new Request(array(
	'params' => array(
		'import_file' => array(
			'type' => 'uri'
		)
	),
	'required' => array('import_file')
));

$params = $req->parseParams($_GET);

$import_file = $params['import_file'];
$tmpfile = tempnam(sys_get_temp_dir(), 'poi-dp-import');
if (!copy($import_file, $tmpfile))
	Response::fail(500, "Unable to retrieve file for import.");

$zip = new ZipArchive();
if ($zip->open($tmpfile) !== TRUE)
	Response::fail(500, "Unable to open zip file.");

$import_file_path = parse_url($import_file, PHP_URL_PATH);
$import_file_name = strrchr($import_file_path, '/');
$countryfile = substr($import_file_name, 1, -4) . '.txt';

$zippedfile = $zip->getStream($countryfile);

if (!$zippedfile)
	Response::fail(500, "Unable to open zipped file contents.");

$fieldnames = array(
	'geonameid',
	'name',
	'asciiname',
	'alternatenames',
	'latitude',
	'longitude',
	'feature_class',
	'feature_code',
	'country_code',
	'cc2',
	'admin1_code',
	'admin2_code',
	'admin3_code',
	'admin4_code',
	'population',
	'elevation',
	'dem',
	'timezone',
	'modification_date'
);

$importer = new CsvImporter($zippedfile, false, $fieldnames, "\t");

$pois = array();

while(($row = $importer->get()) != null) {
	
	$poi_data = array(
	
		'fw_core' => array(
			'category' => $row['feature_code'],
			
			'name' => array(
				'' => $row['name']
			),
			
			'url' => array(
				'' => 'http://www.geonames.org/' . $row['geonameid']
			),
			
			'label' => array(
				'' => $row['name']
			),
			
			'location' => array( 
				'wgs84' => array(
					'latitude' => floatval($row['latitude']),
					'longitude' => floatval($row['longitude'])
				)
			),
			
			// "description": {
			// },
			
			'source' => array(
				'name' => 'geonames.org',
				'website' => 'http://www.geonames.org/',
				'license' => 'http://creativecommons.org/licenses/by/3.0/'
			),
			
			'last_update' => array(
				'timestamp' => $row['modification_date']
			)
			
		),
		
		// TODO: set more than only fw_core component
	);

	$poi_info = $dp->create($poi_data);
	
	$pois[] = $poi_info['uuid'];

}

fclose($zippedfile);
if ($zip->close())
	unlink($tmpfile);

Response::json(array('imported_pois' => $pois));

?>
