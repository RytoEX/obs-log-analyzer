<?php

namespace RytoEX\OBS\LogAnalyzer\Log;

class OBSStudioLogLine
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;

	public $raw_string = null;
	public $line_num;
	public $line_type; // profiler
	public $timestamp;
	public $log_level; // Linux debug logs don't have timestamps - debug, info, warning, error
	public $indent_level;
	public $item_string;
	public $has_parent;
	public $parent_line;
	public $has_children;
	public $children = array();

	public function __construct($raw_string = null, $load_data = true)
	{
		if (isset($raw_string)) {
			$this->raw_string = $raw_string;
			//self::load_data_from_string($this->raw_string);
			if ($load_data === true) {
				$this->load_data();
			}
		}
	}

	public function load_data()
	{
		if(!isset($this->raw_string)) {
			return false;
		}

		$this->timestamp = $this->find_timestamp();
		$this->item_string = $this->find_item_string();
		$this->indent_level = $this->find_indent_level();
		if ($this->is_valid()) {
			
		}
	}

	public function load_data_from_string($raw_string)
	{
		if (!isset($this->raw_string)) {
			$this->raw_string = $raw_string;
		}
		self::load_data();
	}

	// might convert these to use the trait SearchGeneric functions
	public function find_timestamp()
	{
		//$timestamp = substr($this->raw_string, 0, strpos($this->raw_string, ':', strpos($this->raw_string, ".")));
		$timestamp_end_marker_pos = strpos($this->raw_string, ': ');
		if ($timestamp_end_marker_pos === false) {
			return false;
		}
		$timestamp = trim(substr($this->raw_string, 0, $timestamp_end_marker_pos));
		return $timestamp;
	}

	public function find_item_string()
	{
		$item_string = rtrim(substr($this->raw_string, strpos($this->raw_string, ': ')+2));
		return $item_string;
	}

	public function find_indent_level()
	{
		if (!isset($this->item_string)) {
			return false;
		}

		$indent_tab_len = strspn($this->item_string, "\t");
		$indent_full_len = strspn($this->item_string, "\t ");
		$indent_full_string = substr($this->item_string, 0, $indent_full_len);
		$indent_string = ltrim($indent_full_string);
		$indent_level = 0;
		$indent_level += $indent_tab_len + (($indent_full_len - $indent_tab_len) / 2);

		return $indent_level;
	}

	public function get_item_string()
	{
		if (isset($this->item_string))
			return $this->item_string;
	}

	public function get_raw_string()
	{
		if (isset($this->raw_string))
			return $this->raw_string;
	}

	public function has_valid_timestamp()
	{
		if (isset($this->timestamp) && $this->timestamp !== false && strlen($this->timestamp) > 0) {
			return true;
		}
		return false;
	}

	public function has_valid_item_string()
	{
		if (isset($this->item_string) && $this->item_string !== false && strlen($this->item_string) > 0) {
			return true;
		}
		return false;
	}

	public function is_valid()
	{
		if ($this->has_valid_timestamp() && $this->has_valid_item_string()) {
			return true;
		}
		return false;
	}

	public function add_child($child)
	{
		$this->children[] = $child;
		$this->has_children = true;
	}

	public function set_parent($parent)
	{
		$this->parent_line = $parent;
		$this->has_parent = true;
	}

	//public function get_all_children
	public function flag_issues()
	{
		
	}
}
