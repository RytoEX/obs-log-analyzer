<?php

namespace RytoEX\OBS\LogAnalyzer\Module;

// only some modules actually print enough log info for us to care about making objects from them
// browser_source, AMF Encoder
// perhaps make this an abstract class?
class OBSModule
{
	public $raw_string;
	public $filename;
	public $module_name;
	public $version;
	public $data; // array of extra data, like AMF Compile, Runtime, and Library?

	public function get_print_string()
	{
		$print = "module_name: " . $this->module_name . "\n";
		$print .= "filename: " . $this->filename . "\n";
		$print .= "version: " . $this->version . "\n";

		return $print;
	}
}
