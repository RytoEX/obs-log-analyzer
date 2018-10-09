<?php

namespace RytoEX\OBS\LogAnalyzer\Source;

class Scene extends \RytoEX\OBS\LogAnalyzer\SourceSource
{
	public $scene_items; // array of sources

	public function __construct($raw_string = null)
	{
		parent::__construct($raw_string);
		$this->source_type = 'scene';
	}
}
