<?php

namespace RytoEX\OBS\LogAnalyzer\Video;

class Adapter
{
	public $number;
	public $name;
	public $dedicated_vram;
	public $shared_vram;
	public $outputs = array();
	public $is_renderer_loaded;

	public function add_output($output)
	{
		$this->outputs[] = $output;
	}

	public function add_output_from_string($string)
	{
		$output = new \RytoEX\OBS\LogAnalyzer\Video\Output($string);
		$this->add_output($output);
	}

	public function get_output($num = 1)
	{
		$num--;
		if (isset($this->outputs[$num])) {
			return $this->outputs[$num];
		}
	}

	public function get_print_string()
	{
		$print = "number: " . $this->number . "\n";
		$print .= "name: " . $this->name . "\n";
		$print .= "dedicated_vram: " . $this->dedicated_vram . "\n";
		$print .= "shared_vram: " . $this->shared_vram . "\n";

		foreach ($this->outputs as $output) {
			$print .= $output->get_print_string();
		}
		return $print;
	}
}
