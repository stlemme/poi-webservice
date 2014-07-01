<?php

require_once(__DIR__ . '/../lib/poi-data-provider.php');

abstract class StatusItem
{
	private $code;
	private $msg;

	protected function check($msg) {
		$this->msg = $msg;
		$this->code = 'failed';
	}
	
	// protected function result($code, $msg) {
		// $RETURN_CODES = array('failed', 'warning', 'info', 'succeeded');
		// if (in_array($code, $RETURN_CODES))
			// $this->code = $code;
		// $this->msg = $msg;
	// }
	
	public function perform($dp) {
		$this->code = $this->run($dp);
	}
	
	protected abstract function run($dp);
	
	public function msg() { return $this->msg; }
	public function status() { return $this->code; }
	public function heading() { return get_class($this); }
}

class CheckForConfigFile extends StatusItem
{
	function run($dp) {
		$this->check('Config file available');
		$cf = $dp->configFile();
		return (realpath($cf) === FALSE) ? 'failed' : 'succeeded';
	}
}

class CheckMongoExtensionAvailable extends StatusItem
{
	function run($dp) {
		$this->check('PHP mongodb extension loaded');
		return extension_loaded("mongo") ? 'succeeded' : 'failed';
	}
}


class CheckModRewriteEnabled extends StatusItem
{
	function run($dp) {
		$this->check("Apache mod_rewrite extension enabled");
		return in_array('mod_rewrite', apache_get_modules()) ? 'succeeded' : 'warning';
	}
}

class CheckMongoDatabaseConnection extends StatusItem
{
	function run($dp)
	{
	}
}

class SupportedComponents extends StatusItem
{
	function run($dp) {
		$comp = $dp->getSupportedComponents();
		$c = count($comp);
		$this->check('Support for ' . $c . ' components: ' . implode($comp, ', '));
		return ($c > 0) ? 'succeeded' : 'warning';
	}
}


$dp = new POIDataProvider();

$items = array(
	new CheckMongoExtensionAvailable(),
	new CheckModRewriteEnabled(),
	new CheckForConfigFile(),
	new SupportedComponents()
);

?>
<html>
<head>
<title>POI webservice - admin / status page</title>
</head>
<body>
<div class="heading">POI webservice - admin / status page</div>
<div class="briefing">This page gives an over about the status of the POI webservice. It performs several checks and displays the relevant result and additional information.</div>
<div class="checklist">
<?php

	foreach($items as $i)
	{
		$i->perform($dp);

		echo '<div class="' . $i->status() . '">' . PHP_EOL;
		echo '<div class="item">' . $i->heading() . '</div>' . PHP_EOL;
		echo $i->msg() . PHP_EOL;
		echo '</div>' . PHP_EOL . PHP_EOL;
	}

?>
</div>
</body>
</html>