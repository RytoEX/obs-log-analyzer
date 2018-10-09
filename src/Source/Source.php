<?php

namespace RytoEX\OBS\LogAnalyzer\Source;

class Source
{
	public $raw_string;
	public $source_type;
	public $name;
	public $filters = array(); // do we even care? kind of, lots of filters can slow things down.

	public function __construct($raw_string = null)
	{
	}
}
