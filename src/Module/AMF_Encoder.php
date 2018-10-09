<?php

namespace RytoEX\OBS\LogAnalyzer\Module;

class AMF_Encoder extends \RytoEX\OBS\LogAnalyzer\Module\OBSModule
{
	// 17:53:49.731: [AMF Encoder] Version 1.4.3.11 loaded (Compiled: 1.3.0.5, Runtime: 1.4.1.0, Library: 1;4;1;0;16.60.2011;201702100916;CL#1371796).
	// 13:30:08.835: [AMF] Version 1.9.9.11 loaded (Compiled: 1.4.0.0, Runtime: 1.4.2.0, Library: 1;4;2;0;17.10.1711;201704101313;CL#1396327).
	// 16:29:19.133: [AMF] Version 2.1.6 loaded (Compiled: 1.4.2.0, Runtime: 1.4.2.0, Library: 1;4;2;0;17.10.1731;201704242122;CL#1401971).
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;

	public $compiled;
	public $runtime;
	public $library;

	public function __construct($raw_string = null)
	{
		$this->module_name = 'AMF Encoder';
		$this->filename = 'enc-amf'; // Do we assume this, or double check?  Why not both?

		if (isset($raw_string)) {
			$this->raw_string = $raw_string;

			$this->load_from_string($this->raw_string);
		}
	}

	public function load_from_string($string)
	{
		$this->version = $this->find_string($string, ' Version ', ' loaded');
		$this->compiled = $this->find_string($string, 'Compiled: ', ',');
		$this->runtime = $this->find_string($string, 'Runtime: ', ',');
		$this->library = $this->find_string($string, 'Library: ', ')');
	}

	public function get_print_string()
	{
		$print = parent::get_print_string();
		$print .= "compiled: " . $this->compiled . "\n";
		$print .= "runtime: " . $this->runtime . "\n";
		$print .= "library: " . $this->library . "\n";
	}

	public function set_filename($string)
	{
		$this->filename = $string;
	}
}
