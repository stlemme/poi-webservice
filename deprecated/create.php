<?php

// error_reporting (E_NOTICE);

// MongoLog::setModule( MongoLog::ALL );
// MongoLog::setLevel( MongoLog::ALL );

include("config.php");


// MongoDB connection

$m = new MongoClient(
//	"localhost:27017",
	$config["db_host"],
	array(
//		"replicaSet" => false,
//		"connect" => false,
		"db" => $config["database"],
		"username" => $config["db_user"],
		"password" => $config["db_password"]
	)
);

$db = $m->selectDB($config["database"]);
$pois = $db->selectCollection($config["collection"]);


/////////////////////////////////////////////////


class POI {
	public $id;
	public $structure = null;
	public $features = array();
	public $contents = array();
	public $datasource = null;
}

function guidv4()
{
    $data = openssl_random_pseudo_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// $p = new POI();
// echo json_encode($p);

$result = array(
	"status" => "ok",
	"location" => null
);

///////////////////////////////////////////////////////////////

$data = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
$p = json_decode($data);

// TODO: validate content of $p

// generate globally unique id of poi if none
if (!isset($p->id)) {
	$p->id = guidv4();
}

//$id = str_replace("-", "", $id);
//$p->_id = new MongoId($id);
//print_r($id);
//print_r($p);

// tranfer date time
$p->source->created = new MongoDate(strtotime($p->source->created));
$p->source->updated = new MongoDate(strtotime($p->source->updated));


// insert the poi instance

try {
	$r = $pois->insert($p);
} catch (MongoException $e) {
	print_r($e);
}

// $result["count"] = $cursor->count();

// foreach ($cursor as $p) {
//	$result["result"][] = $p;
// }


///////////////////////////////////////////////////////////////

// $uri = $_SERVER["HTTP_PROTOCOL"] . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "?id=" . $p->id;
$uri = $config["service_endpoint"] . "?id=" . $p->id;

header("HTTP/1.0 201 Created");
header("Location: " . $uri);
// header("Content-Type: application/json; charset=utf-8");

// print_r($p);
// print_r($_SERVER);
// print_r(file_get_contents('php://input'));
// print_r($HTTP_RAW_POST_DATA);
// echo json_encode($result);

?>
