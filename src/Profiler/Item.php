<?php

namespace RytoEX\OBS\LogAnalyzer\Profiler;

class Item extends \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLogLine
{
	public $item_type; // init_root, init_item, init_module, thread_root, thread_item // init, thread
	public $has_item_info = false;
	public $item_info;
	public $name;
	public $run_time;
	public $min;
	public $median;
	public $max;
	public $percentile99;
	public $calls_per_parent_call;
	public $percent_below_threshold;
	public $percent_within_puramai_2;
	public $percent_below_2;
	public $percent_above_2;

	public function __construct($raw_string = null, $load_data = true)
	{
		parent::__construct($raw_string);
		if (isset($raw_string)) {
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

		parent::load_data();

		$this->has_item_info = $this->has_info();
		$this->item_type = $this->find_type();
		$this->name = $this->find_name();
		$this->item_info = $this->find_info();
		$this->indent_level = $this->find_indent_level();

		if ($this->item_type === "init") {
			$this->run_time = $this->find_run_time();
		} elseif ($this->item_type === "thread") {
			$this->min = $this->find_stat_value("min");
			$this->median = $this->find_stat_value("median");
			$this->max = $this->find_stat_value("max");
			$this->percentile99 = $this->find_stat_value("99th percentile");
			$this->calls_per_parent_call = $this->find_parent_calls();
			$this->percent_below_threshold = $this->find_percent_below_threshold();
		} elseif ($this->item_type === "time_between_calls") {
			$this->min = $this->find_stat_value("min");
			$this->median = $this->find_stat_value("median");
			$this->max = $this->find_stat_value("max");
			//puramai
		}
	}

	public function load_data_from_string($raw_string)
	{
		parent::load_data_from_string($raw_string);
		$this->load_data();
	}

	public function has_info()
	{
		if (strpos($this->item_string, '):') !== false) {
			$this->has_item_info = true;
			return true;
		}
	}

	// @todo: convert to use find_string
	public function find_name()
	{
		if (isset($this->item_string)) {
			if ($this->has_item_info) {
				$name = substr($this->item_string, 0, strpos($this->item_string, '('));
			} else {
				$name = substr($this->item_string, 0, strpos($this->item_string, ': '));
			}
		} elseif (isset($this->raw_string)) {
			if ($this->has_item_info) {
				$name = substr($this->raw_string, strpos($this->raw_string, ': '), strpos($this->raw_string, '('));
			} else {
				$name = substr($this->raw_string, strpos($this->raw_string, ': '), strpos($this->raw_string, ': '));
			}
		}
		$name = ltrim($name, ' ┃┣┗');
		return $name;
	}

	public function find_type()
	{
		if ((strpos($this->item_string, ' min=') === false)
			&& (strpos($this->item_string, ' median=') === false)
			&& (strpos($this->item_string, ' max=') === false)
			&& (strpos($this->item_string, 'ms') !== false)) {
			return "init";
		} elseif (strpos($this->item_string, 'within') !== false) {
			return "time_between_calls";
		} elseif ((strpos($this->item_string, ' min=') !== false)
			&& (strpos($this->item_string, ' median=') !== false)
			&& (strpos($this->item_string, ' max=') !== false)) {
			return "thread";
		} else {
			return false;
		}
	}

	public function find_info()
	{
		$info = false;
		if ($this->has_item_info) {
			$lparen = strpos($this->item_string, '(');
			$rparen = strpos($this->item_string, ')', $lparen);
			$info = substr($this->item_string, $lparen + 1, $rparen - $lparen - 1);
		}
		return $info;
	}

	public function find_indent_level()
	{
		if (!isset($this->item_string)) {
			return false;
		}

		// '┃┣┗' - these characters are each strlen of 3
		$indent_space_len = strcspn($this->item_string, '┃┣┗');
		$indent_full_len = strspn($this->item_string, ' ┃┣┗');
		$indent_full_string = substr($this->item_string, 0, $indent_full_len);
		$indent_string = ltrim($indent_full_string);
		$indent_level = 0;
		if ($indent_full_len === 0) {
			$indent_level = 0;
		} elseif ($indent_space_len === 1) {
			$indent_level = 1;
		} elseif ($indent_space_len > 1) {
			$indent_level = 1 + (int) floor($indent_space_len / 2);
		}

		$indent_level += substr_count($indent_string, ' ');

		return $indent_level;
	}

	public function find_stat_value($value_name)
	{
		// @todo: switch to specify units, assume "ms" if null?
		return $this->find_value($this->item_string, $value_name, 'ms');
	}

	public function find_run_time()
	{
		$run_time_pos = strpos($this->item_string, ': ') + 2;
		$ms_pos = strpos($this->item_string, 'ms', $run_time_pos);
		$run_time = trim(substr($this->item_string, $run_time_pos, $ms_pos - $run_time_pos), chr(0xC2).chr(0xA0).' ');
		return $run_time;
	}

	public function find_parent_calls()
	{
		$parent_calls = false;
		$parent_calls_end = strpos($this->item_string, ' calls per parent call');
		if ($parent_calls_end !== false) {
			$parent_calls_start = strrpos($this->item_string, ', ') + 2;
			$parent_calls = substr($this->item_string,
					$parent_calls_start,
					$parent_calls_end - $parent_calls_start);
		}
		return $parent_calls;
	}

	public function find_percent_below_threshold()
	{
		$percent_below_threshold = false;
		$percent_below_threshold_end = strpos($this->item_string, ' below ');
		if ($percent_below_threshold_end !== false) {
			$percent_below_threshold_start = strrpos($this->item_string, ', ') + 2;
			$percent_below_threshold = substr($this->item_string,
					$percent_below_threshold_start,
					$percent_below_threshold_end - $percent_below_threshold_start);
		}
		return $percent_below_threshold;
	}

	public function get_min()
	{
		return $this->min;
	}

	public function get_median()
	{
		return $this->median;
	}

	public function get_max()
	{
		return $this->max;
	}

	public function get_run_time()
	{
		return $this->run_time;
	}

	public function get_percent_below_threshold($with_percent_sign = false)
	{
		if (!isset($this->percent_below_threshold)) {
			return false;
		}
		if ($with_percent_sign) {
			return $this->percent_below_threshold;
		} else {
			return rtrim($this->percent_below_threshold, '%');
		}
	}

	public function is_type_init()
	{
		if (isset($this->item_type) &&
			$this->item_type === 'init') {
			return true;
		} else {
			return false;
		}
	}

	public function is_type_thread()
	{
		if (isset($this->item_type) &&
			$this->item_type === 'thread') {
			return true;
		} else {
			return false;
		}
	}
}
