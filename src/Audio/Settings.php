<?php

namespace RytoEX\OBS\LogAnalyzer\Audio;

class Settings
{
	public $samples_per_sec;
	public $speakers;

	public function get_print_string()
	{
		$print = "";
		$print .= "samples per sec: " . $this->samples_per_sec . "\n";
		$print .= "speakers: " . $this->speakers . "\n";
		return $print;
	}
}
