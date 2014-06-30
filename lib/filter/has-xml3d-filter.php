<?php

require_once(__DIR__ . '/has-attribute-filter.php');


class HasXml3dFilter extends HasAttributeFilter
{
	const FilterParameterName = 'hasXml3d';
	const AttributeName = 'fw_xml3d.model';
	
	public function __construct() {
		parent::__construct(self::AttributeName, self::FilterParameterName);
	}
}

?>