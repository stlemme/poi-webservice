<?php

class CsvImporter 
{ 
	private $fp; 
	private $header; 
	private $delimiter; 
	
	public function __construct($file_name, $parse_header=false, $header=null, $delimiter="\t") 
	{ 
		$this->fp = fopen($file_name, "r"); 
		$this->delimiter = $delimiter; 

		if ($parse_header) { 
			$this->header = fgetcsv($this->fp, 0, $this->delimiter); 
		} else if ($header != null) {
			$this->header = $header;
		} else {
			$this->header = null;
		}
	} 

	public function __destruct() 
	{
		if ($this->fp) 
			fclose($this->fp); 
	}

	public function get()
	{
		if ($this->fp == null)
			return null;
		
		$row = fgetcsv($this->fp, 0, $this->delimiter);
		
		if ($row == FALSE)
			return null;
		
		if ($this->header == null)
			return $row;

		foreach ($this->header as $i => $heading_i) 
			$assoc_row[$heading_i] = $row[$i]; 

		return $assoc_row;
	} 
}

?>