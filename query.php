<?php

// error_reporting(E_ALL);


require_once("config.php");
require_once("jsv4-php/jsv4.php");
require_once("jsv4-php/schema-store.php");
// echo "Service: " . $config["service_endpoint"] . PHP_EOL;

// TODO: quota check


// MongoDB connection

$dbopts = array(
		"db" => $config["database"]
);

if (isset($config["db_user"]) && $config["db_user"] != null)
	$dbopts["username"] = $config["db_user"];
	
if (isset($config["db_password"]) && $config["db_password"] != null)
	$dbopts["password"] = $config["db_password"];

// $m = new MongoClient();
$m = new MongoClient(
//      "localhost:27017",
        $config["db_host"],
		$dbopts
);

//$dbs = $m->listDBs();
$db = $m->selectDB($config["database"]);

//$collections = $db->getCollectionNames();
$pois = $db->selectCollection($config["collection"]);


/////////////////////////////////////////////////


$result = array(
	"status" => "ok",
	"result" => array(),
	"count_query_mongo" => null,
	"count_query_approximate" => null,
	"count_return" => null,
	"count_total" => null
);

$result["count_total"] = $pois->count();


///////////////////////////////////////////////////////////////


function format_datetime($dt)
{
	return date("c", $dt->sec);
}

function format_self_reference($id)
{
	global $config;
	// print_r($config["service_endpoint"]);
	return $config["service_endpoint"] . "?id=" . $id;
}

function format_datasource($ds, $id)
{
	//print_r($ds["created"]);
	//print_r();
	
	if(isset($ds->created)) $ds->created = format_datetime($ds->created);
	if(isset($ds->updated)) $ds->updated = format_datetime($ds->updated);
	if(!isset($ds->href)) $ds->href = format_self_reference($id);
	return $ds;
}

function format_guid($guid)
{
	$pattern = "/([a-f0-9]{8})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{12})/";
	$matches = array();
	preg_match($pattern, strtolower($guid), $matches);
	print_r(strtolower($guid));
	print_r($matches);
	return $matches[1] . "-" . $matches[2] . "-" . $matches[3] . "-" . $matches[4] . "-" . $matches[5];
}

///////////////////////////////////////////////////////////////


function arrayToObject($d)
{
	// TODO: check for a faster implementation
	return json_decode(json_encode($d));
	
	if (is_array($d)) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return (object) array_map(__FUNCTION__, $d);
	}
	else {
		// Return object
		return $d;
	}
}

///////////////////////////////////////////////////////////////


function getDistance($lat1, $lon1, $lat2, $lon2)
{
	// distance in meters
	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = abs(round(rad2deg(acos($dist)) * 60.0 * 1.1515 * 1.609344 * 1000.0, 2));
	
	return $dist;
}

// function getDistance2($lat1, $lon1, $lat2, $lon2)
// {
	// $R = 6371000; // meters
	
	// $dLat = sin(deg2rad($lat1 - $lat2) * 0.5);
	// $dLon = sin(deg2rad($lon1 - $lon2) * 0.5);
	// $lat1 = deg2rad($lat2);
	// $lat2 = deg2rad($lat1);

	// $a = sin($dLat) * sin($dLat) + sin($dLon) * sin($dLon) * cos($lat1) * cos($lat2); 
	// $c = 2.0 * atan2(sqrt($a), sqrt(1.0-$a));
	
	// return round($R * $c, 2);
// }

function getDirection1($lat1, $lon1, $lat2, $lon2)
{
	// code for direction in degrees
	$dlat = deg2rad($lat1 - $lat2);
	$dlon = deg2rad($lon1 - $lon2);
	$y = sin($dlon) * cos($lat2);
	
	$x = cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos($dlon);
	$direct = round(rad2deg(atan2($y, $x)), 2);
	if ($direct < 0.0)
		$direct = $direct + 360.0;
	return $direct;
}

function getDirection2($lat1, $lng1, $lat2, $lng2)
{
	$dLon = ($lng2-$lng1);
	$y = sin($dLon) * cos($lat2);
	$x = cos($lat1)*sin($lat2) - sin($lat1)*cos($lat2)*cos($dLon);
	$brng = rad2deg(atan2($y, $x));
	return 360 - (($brng + 360) % 360);
}

// TODO: implement the correct one
function getDirection3($lat1, $lng1, $lat2, $lng2)
{
	$dLat = ($lat2-$lat1);
	$dLon = ($lng2-$lng1);
	
	return 0.0;
}

function calculateDistanceWGS($flat, $flong, $lat, $long, $tf, $bounds)
{
	return getDistance($flat, $flong, $lat, $long);
	
	// TODO: take tf and bounds into account
}

function calculateDirectionWGS($flat, $flong, $lat, $long, $tf, $bounds)
{
	return getDirection3($lat, $long, $flat, $flong);
	
	// TODO: take tf and bounds into account
}


///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////


$query = array();
$projection = array();

$calcDist = false;
$calcDir = false;

// limit

$limit = $config["default_limit"];
if (isset($_GET["limit"]))
	$limit = $_GET["limit"];
	
if ($limit > $config["max_limit"])
	$limit = $config["max_limit"];


// id -> single POI

if (isset($_GET["id"]))
{
	$id = strtolower($_GET["id"]);
	
	$pattern = "/([a-f0-9]{8})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{12})/";
	if (preg_match($pattern, $id) == 1) {
		$query["id"] = $id;
		$limit = 1;
	} else {
		$result["status"] = "invalid_parameter";
	}
}


// tag

if (isset($_GET["tag"]))
{
	$tag = $_GET["tag"];
	
	$query["contents"] = array(
		'$elemMatch' => array(
			"type" => "tag",
			"name" => $tag
		)
	);
}


// bbox

if (isset($_GET["bbox"]))
{
	$matches = array();
	$pattern = "/^(-?(?:\d+|\d*\.\d+)),(-?(?:\d+|\d*\.\d+)),(-?(?:\d+|\d*\.\d+)),(-?(?:\d+|\d*\.\d+))$/";
	
	if (preg_match($pattern, $_GET["bbox"], $matches) == 1)
	{
		$bbox = array(
			"minLon" => (float)$matches[1],
			"minLat" => (float)$matches[2],
			"maxLon" => (float)$matches[3],
			"maxLat" => (float)$matches[4]
		);
		// print_r($bbox);
	}
}


// position

if (isset($_GET["long"]) && isset($_GET["lat"]))
{
	$calcDist = true;
	
	// TODO: check ranges
	$long = $_GET["long"];
	$lat = $_GET["lat"];
	
	if (isset($_GET["dist"]))
	{
		$dist = $_GET["dist"]; // in meters
		$d = 360.0 * $dist / 6371000.0;
		
		$bbox = array(
			"minLon" => $long-$d,
			"minLat" => $lat-$d,
			"maxLon" => $long+$d,
			"maxLat" => $lat+$d
		);
		// $query["features"] = array(
			// '$elemMatch' => array(
				// "method" => "wgs84",
				// "long" => array('$gt' => $long-$d, '$lt' => $long+$d),
				// "lat" => array('$gt' => $lat-$d, '$lt' => $lat+$d)
			// )
		// );
	}
}


// bbox for query - either from bbox param or from dist param

if (isset($bbox))
{
	$query["features"] = array(
		'$elemMatch' => array(
			"method" => "wgs84",
			"long" => array('$gt' => $bbox["minLon"], '$lt' => $bbox["maxLon"]),
			"lat" => array('$gt' => $bbox["minLat"], '$lt' => $bbox["maxLat"])
		)
	);
}

// direction

if (isset($_GET["direction"]))
	$calcDir = (strtolower($_GET["direction"]) == "true");

	
// use JSONP instead of JSON with callback

$jsoncb = isset($_GET["jsoncallback"]) ? $_GET["jsoncallback"] : null;


///////////////////////////////////////////////////////////////


// _id field

$projection["_id"] = 0;


///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

// print_r($query);

if ($result["status"] == "ok")
{

	$cursor = $pois->find($query, $projection);
	
	// TODO: error check the cursor

	$result["count_query_mongo"] = $cursor->count();
	$result["count_query_approximate"] = $cursor->count();
	
	$store = new SchemaStore();
	$schema = json_decode(file_get_contents($config["schema"]));

	$schemaUrl = "http://example.com/poi-schema";
	
	$store->add($schemaUrl, $schema);
	$schema = $store->get($schemaUrl);

	// echo "<pre>";
	// print_r($schema);
	// print_r($store);
	// echo PHP_EOL .PHP_EOL .PHP_EOL;
	// echo "</pre>";
	
	$json_errors = array(
		JSON_ERROR_NONE => 'Es ist kein Fehler aufgetreten',
		JSON_ERROR_DEPTH => 'Die maximale Stacktiefe wurde erreicht',
		JSON_ERROR_CTRL_CHAR => 'Steuerzeichenfehler, möglicherweise fehlerhaft kodiert',
		JSON_ERROR_SYNTAX => 'Syntaxfehler',
	);
	echo 'Letzter Fehler : ', $json_errors[json_last_error()], PHP_EOL, PHP_EOL;

	
	
	foreach ($cursor as $p) {

		$p = arrayToObject($p);
		
		$result["count_query_approximate"] -= 1;

		if (isset($p->source))
			$p->source = format_datasource($p->source, $p->id);

		if (!isset($p->meta))
			$p->meta = array();
		

		$wgs84 = null;
		$bounds = null;

		if ($calcDist || $calcDir)
		{
			foreach($p->features as $f) {
				// print_r($f);
				
				if ($f->method == "wgs84")
				{
					$wgs84 = $f;
					break;
				}
			}

			// if no wgs84 feature skip this poi
			if (!$wgs84) continue;

			if (isset($p->structure))
				if (isset($p->structure->bounds))
					$bounds = $p->structure->bounds;
		}


		if ($calcDist)
		{
			$poiDist = calculateDistanceWGS($f->lat, $f->long, $lat, $long, $f->transformation, $bounds);
			
			$m = new stdClass;
			$m->data = "distance_from_position";
			$m->distance = $poiDist;
			$m->unit = "meters";
			$p->meta[] = $m;

			// skip pois beyond the specified distance
			if (isset($dist) && ($poiDist > $dist))
				continue;
		}

		if ($calcDir)
		{
			$poiDir = calculateDirectionWGS($f->lat, $f->long, $lat, $long, $f->transformation, $bounds);
			
			$m = new stdClass;
			$m->data = "direction_from_position";
			$m->direction = $poiDir;
			$m->unit = "degree";
			$p->meta[] = $m;
		}


		// clean up
		
		// remove empty components (contents, features, relations, meta)
		
		// foreach($p as $prop => $value)
		//	if (count($value) == 0)
		//		unset($p["$prop"]);
				
		// TODO: clean up more unnecessary data structures
		
		
		// TODO: validate the result data
		//print_r($p);
		$valres = Jsv4::validate($p, $schema);
		if (!$valres->valid) {
			echo "<br/><br/><pre>";
			print_r($valres->errors);
			echo "</pre>";
			
			continue;
		}
		
		$result["result"][] = $p;

		$result["count_query_approximate"] += 1;
		
		// force limited return
		if (count($result["result"]) >= $limit)
			break;
	}

	$result["count_return"] = count($result["result"]);

}

///////////////////////////////////////////////////////////////

header("HTTP/1.1 200 Found");

if ($jsoncb) {
	header("Content-Type: application/javascript; charset=utf-8");
	echo $jsoncb . "(" . json_encode($result) . ")";
} else {
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($result);
}

// print_r($result);

?>
