<?php

require_once(__DIR__ . '/../lib/poi-data-provider.php');
require_once(__DIR__ . '/../lib/request.php');
require_once(__DIR__ . '/../lib/response.php');
require_once(__DIR__ . '/../lib/utils.php');


$req = new Request();
$dp = new POIDataProvider();


$req->checkMethod('POST', "You must use HTTP POST to register a new POI component!");

$comp_schema = $req->body();

$comp_name = Utils::json_path($comp_schema, 'title');

if ($comp_name === null)
	Response::fail(400, 'Component schema requires title matching the name of the data component.');
	
if (!Utils::validate_comp_name($comp_name))
	Response::fail(400, 'Component requires valid name of the data component.');


// TODO: refactor component schema file access
$comp_file = realpath(__DIR__ . '/../' . $dp->config('schema.path') . '/components') . '/' . $comp_name . '.json';

if (file_exists($comp_file))
	echo "Warning! Component schema '$comp_name' will be overridden." . PHP_EOL;

$comp_content = Utils::json_encode($comp_schema);
$success = file_put_contents($comp_file, $comp_content);
if ($success === false)
	Response::fail(500, 'Unable to write component file.');

echo "Component schema '$comp_name' successfully registered.";

?>