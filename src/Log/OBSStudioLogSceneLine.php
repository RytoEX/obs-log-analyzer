<?php

namespace RytoEX\OBS\LogAnalyzer\Log;

class OBSStudioLogSceneLine extends \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLogLine
{
	public $source_type;
	public $source_name;

	public function __construct($raw_string = null)
	{
		//echo "scene_line construct beg\n";
		parent::__construct($raw_string);
		$this->line_type = 'scene_line';
		//echo "scene_line construct end\n";
	}

	public function load_data()
	{
		if(!isset($this->raw_string)) {
			return false;
		}

		parent::load_data();

		$this->line_type = $this->find_type();
		$this->indent_level = $this->find_indent_level();

		if ($this->line_type === 'scene') {
			$this->source_name = $this->find_string($this->item_string, "- scene '", "':");
			$this->source_type = 'scene';
		} elseif ($this->line_type === 'source') {
			$this->source_name = $this->find_string($this->item_string, "- source: '", "' (");
			$this->source_type = $this->find_string($this->item_string, " (", ")");
		} elseif ($this->line_type === 'scene_line') {
		}
	}

	public function load_data_from_string($raw_string)
	{
		//echo "scene_line load beg\n";
		parent::load_data_from_string($raw_string);
		$this->load_data();
		//$this->raw_string = $raw_string;
		//echo "scene_line load end\n";
	}

	public function find_type()
	{
		if (isset($this->item_string)) {
			$string = $this->item_string;
		} elseif (isset($this->raw_string)) {
			$string = $this->item_string;
		} else {
			return false;
		}
		if (strpos($string, '- scene') !== false) {
			return 'scene';
		} elseif (strpos($string, '- source') !== false) {
			return 'source';
		} else {
			return null;
		}
	}

	public function find_indent_level()
	{
		if (!isset($this->item_string)) {
			return false;
		}

		$indent_space_len = strspn($this->item_string, " ");
		$indent_level = $indent_space_len / 4;

		return $indent_level;
	}
}
