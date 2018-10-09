<?php

namespace RytoEX\OBS\LogAnalyzer\Config;

class OSData
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;

	public $win10_data;

	public function __construct()
	{
		
	}

	public function fetch_new_data()
	{
		
	}

	public function fetch_windows_data()
	{
		
	}

	public function fetch_win10_data()
	{
		$win10scraper = new \RytoEX\OBS\LogAnalyzer\Config\Win10Scraper;
	}
}
