<?php

namespace RytoEX\OBS\LogAnalyzer\Stats;

class Tracker
{
	use \RytoEX\OBS\LogAnalyzer\Utils\JSONFileIOTrait;

	private $config;
	public $data_dir;

	public function __construct()
	{
		$do_nothing = true;
	}

	public function init()
	{
		//$this->config = 
		return true;
	}

	public function load_json()
	{
	}

	public function create_updated_json()
	{
	}

	public function save_json($version_info, $file = null)
	{
		if (!isset($version_info))
			return false;

		if (!isset($file))
			$file = $this->info_file;

		$json = $this->create_updated_json($version_info, $file);
		if ($json === false)
			return false;

		$res = $this->write_json_file($file, $json, true, true);
		if (is_int($res) && $res >= 0)
			return false;

		return true;
	}
}
