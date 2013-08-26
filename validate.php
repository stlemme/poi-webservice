<?php

// error_reporting(E_ALL);


require_once("config.php");
require_once("jsv4-php/jsv4.php");
require_once("jsv4-php/schema-store.php");


/////////////////////////////////////////////////

$result = array(
	"status" => "ok",
	"message" => null
);

/////////////////////////////////////////////////
	
$store = new SchemaStore();
$schema = json_decode(file_get_contents($config["schema"]));

$schemaUrl = $config["schema_url"];

$store->add($schemaUrl, $schema);
$schema = $store->get($schemaUrl);

$json_errors = array(
	JSON_ERROR_DEPTH => "Reached the maximum stack depth",
	JSON_ERROR_CTRL_CHAR => "Control character error, probably wrong encoding",
	JSON_ERROR_SYNTAX => "Syntax error"
);

$schemaError = json_last_error();

if ($schemaError != JSON_ERROR_NONE) {
	$result["status"] = "schema_error";
	$result["message"] = $json_errors[$schemaError];
}

if ($result["status"] == "ok")
{
	$data = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
	$p = json_decode($data);

	$valres = Jsv4::validate($p, $schema);

	if (!$valres->valid) {
		$result["status"] = "validation_failed";
		// print_r($valres->errors);
		$result["message"] = $valres->errors;
	}
}

///////////////////////////////////////////////////////////////

header("HTTP/1.1 200 Found");
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result);

// print_r($result);

?>
