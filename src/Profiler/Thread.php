<?php

namespace RytoEX\OBS\LogAnalyzer\Profiler;

class Thread extends \RytoEX\OBS\LogAnalyzer\Profiler\Item
{
	public $thread_info;

	public function __construct($thread_item_string)
	{
		parent::__construct($thread_item_string);
	}
}
