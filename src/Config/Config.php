<?php

namespace RytoEX\OBS\LogAnalyzer\Config;

class Config
{
	public $this_dir;		// this file's directory
	public $app_dir;		// the parent application's root directory
	public $config_dir;		// where to store config data
	public $data_dir;		// config/data
	public $cache_dir;		// config/data/cache
	public $os_info_dir;	// config/data/os_info

	public function __construct($init = true, $config_dir = '')
	{
		$this->this_dir = __DIR__;

		if (empty($config_dir))
			$this->config_dir = $this->this_dir;
		else
			$this->config_dir = $config_dir;

		$this->data_dir = $this->config_dir . '/data';
		$this->cache_dir = $this->data_dir . '/cache';
		$this->os_info_dir = $this->data_dir . '/os_info';

		if ($init) {
			$this->make_data_dir();
			$this->make_cache_dir();
			$this->make_os_info_dir();
		}
	}

	public function does_cache_dir_exist()
	{
		return is_dir($this->cache_dir);
	}

	public function make_cache_dir()
	{
		if (!$this->does_cache_dir_exist()) {
			mkdir($this->cache_dir);
		}
	}

	public function does_data_dir_exist()
	{
		return is_dir($this->data_dir);
	}

	public function make_data_dir()
	{
		if (!$this->does_data_dir_exist()) {
			mkdir($this->data_dir);
		}
	}

	public function does_os_info_dir_exist()
	{
		return is_dir($this->os_info_dir);
	}

	public function make_os_info_dir()
	{
		if (!$this->does_os_info_dir_exist()) {
			mkdir($this->os_info_dir);
		}
	}

	public function set_app_dir($dir)
	{
		$this->app_dir = $dir;
	}

	public function set_config_dir($dir)
	{
		$this->config_dir = $dir;
	}

	public function set_data_dir($dir)
	{
		$this->data_dir = $dir;
	}

	public function set_cache_dir($dir)
	{
		$this->cache_dir = $dir;
	}

	public function set_os_info_dir($dir)
	{
		$this->os_info_dir = $dir;
	}
}
