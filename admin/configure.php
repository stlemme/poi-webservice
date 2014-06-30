<?php

require_once(__DIR__ . '/../lib/poi-data-provider.php');
require_once(__DIR__ . '/../lib/request.php');
require_once(__DIR__ . '/../lib/response.php');
require_once(__DIR__ . '/../lib/utils.php');


$req = new Request();

$configFile = POIDataProvider::configFile();
if (!file_exists($configFile))
	copy($configFile . '.sample', $configFile);

$configFilePath = realpath($configFile);
if ($configFilePath === false)
	Response::fail(500, 'Failed to load config file.');

$config_content = file_get_contents($configFilePath);
$old_config = Utils::json_decode($config_content);
if ($old_config === null)
	$old_config = array();

if ($req->method() == 'POST')
{
	$updated_config = $req->body();

	$new_config = Utils::json_update($old_config, $updated_config);
	$config_content = Utils::json_encode($new_config);

	$success = file_put_contents($configFilePath, $config_content);
	if ($success === false)
		Response::fail(500, 'Unable to write config file.');
		
} else {
	$new_config = $old_config;
}

Response::json($new_config);

?>