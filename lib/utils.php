<?php


class Utils
{
	public static function generate_guidv4()
	{
		$data = openssl_random_pseudo_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
	
	private static $guidv4_pattern = "/([a-f0-9]{8})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{4})-([a-f0-9]{12})/";

	public static function validate_guidv4($id)
	{
		$id = strtolower($id);
		
		if (preg_match(self::$guidv4_pattern, $id) == 1)
			return $id;
		
		return null;
	}

	private static $uri_pattern = "/([a-z][a-z0-9\+\-]*):\/\/(.+)/";

	public static function validate_uri($uri)
	{
		if (preg_match(self::$uri_pattern, $uri) == 1)
			return $uri;

		return null;
	}
	
	public static function validate_pattern($value, $pattern)
	{
		if (preg_match($pattern, $value) == 1)
			return $value;

		return null;
	}

	public static function json_encode($data)
	{
		return json_encode($data);
	}
	
	public static function json_decode($json, $assoc_array = true, $debug = false)
	{
		$json_errors = array(
			JSON_ERROR_DEPTH => "The maximum stack depth has been exceeded",
			JSON_ERROR_STATE_MISMATCH => "Invalid or malformed JSON",
			JSON_ERROR_CTRL_CHAR => "Control character error, possibly incorrectly encoded",
			JSON_ERROR_SYNTAX => "Syntax error"
		);
		
		$data = json_decode($json, $assoc_array);
		$err = json_last_error();
		
		if ($debug) {
			if ($err != JSON_ERROR_NONE)
				die('JSON error : ' . $json_errors[$err]);
		}

		return $data;
	}
	
	public static function json_path($poi_data, $path)
	{
		$current = $poi_data;
		$path_parts = explode('.', $path);
		while(($elem = array_shift($path_parts)) != null)
		{
			if (!array_key_exists($elem, $current))
				return null;
				
			$current = $current[$elem];
		}
		return $current;
	}
	
	public static function json_update($data, $update)
	{
		$new_data = array_replace_recursive($data, $update);
		// TODO: remove "deleted" (null) fields
		return $new_data;
	}

	
	public static function extractCoordinates($poi_data, &$lon, &$lat)
	{
		$wgs84 = self::json_path($poi_data, 'fw_core.location.wgs84');
		if ($wgs84 == null)
			return false;
		
		$lon = $wgs84['longitude'];
		$lat = $wgs84['latitude'];
		
		return true;
	}

	
	public static function className($filename)
	{
		$filename = basename($filename, '.php');
		$s = str_replace('-', ' ', $filename);
		$t = ucwords($s);
		return str_replace(' ', '', $t);
	}
	
	public static function loadClassFromFile($filename, $path = null)
	{
		if ($path == null)
			$path = __DIR__;
		
		$filename = realpath($path . '/' . $filename . '.php');
		if (!file_exists($filename))
			return null;
		
		include_once($filename);

		return Utils::className($filename);
	}
}

?>