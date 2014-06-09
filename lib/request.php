<?php

require_once(__DIR__ . '/response.php');
require_once(__DIR__ . '/utils.php');


class Request
{
	private $params;
	private $required;
	private $optional;
	
	public function __construct($requirements = null) {
		if ($requirements == null)
			$this->params = null;
		
		$this->params   = isset($requirements['params'])   ? $requirements['params']   : null;
		$this->required = isset($requirements['required']) ? $requirements['required'] : array();
		$this->optional = isset($requirements['optional']) ? $requirements['optional'] : array();
	}
	
	public function checkMethod($method, $message) {
		if ($_SERVER['REQUEST_METHOD'] != $method)
			Response::fail(400, $message);
	}

	private function convertParam($value, $target, $prop)
	{
		switch ($target) {
			case "string": return $value;
			case "uuid":   return Utils::validate_guidv4($value);
			case "uri":    return Utils::validate_uri($value);
			case "float":  return is_numeric($value) ? floatval($value) : null;
			case "int":    return is_numeric($value) ? intval($value) : null;
			case "enum":   return in_array($value, $prop['values']) ? $value : null;
			default:       return null;
		}
	}

	private function handleParam($pname, $pprop, $pvalue)
	{
		$ptype = isset($pprop["type"]) ? $pprop["type"] : "string";
		$parray = isset($pprop["array"]) ? $pprop["array"] : false;
		
		if ($parray) {
			$pval_arr = explode(",", $pvalue);
			$pval = array();
			foreach($pval_arr as $pval_arr_var) {
				$val = $this->convertParam($pval_arr_var, $ptype, $pprop);
				if ($val == null) {
					$pval = null;
					break;
				}
				$pval[] = $val;
			}
		} else {
			$pval = $this->convertParam($pvalue, $ptype, $pprop);
		}
		
		if ($pval == null)
			Response::fail(400, "'$pname' parameter must be of type '$ptype'!");
				
		return $pval;
	}
	
	public function parseParams($vars)
	{
		$params = array();
		
		if ($this->params == null)
			return $params;
		
		foreach ($this->required as $pname)
		{
			if (!isset($vars[$pname]))
				Response::fail(400, "'$pname' parameter must be specified!");
			
			$pprop = $this->params[$pname];
			$pval = $vars[$pname];
			
			$params[$pname] = $this->handleParam($pname, $pprop, $pval);
		}

		foreach ($this->optional as $pname)
		{
			if (!isset($vars[$pname]))
				continue;
			
			$pprop = $this->params[$pname];
			$pval = $vars[$pname];
			
			$params[$pname] = $this->handleParam($pname, $pprop, $pval);
		}
		
		return $params;
	}
	
	public function body()
	{
		$data = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
		$request_array = Utils::json_decode($data);

		if ($request_array == NULL)
			Response::fail(400, "Error decoding request payload as JSON!");
			
		return $request_array;
	}

}

?>
