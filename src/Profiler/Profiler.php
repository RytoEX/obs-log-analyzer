<?php

namespace RytoEX\OBS\LogAnalyzer\Profiler;

class Profiler
{
	public $raw_string = null;
	// Use arrays instead of properties for each item?
	// This allows us to have multiple items with the same name (which can happen with threads).
	public $data = array(); // root keys:  init, thread, time_between_calls
	public $profiler_lines = array();
	public $init_data = array();
	public $thread_lines = array();
	public $run_program_init; // root profiler init object
	public $obs_video_threads = array();
	public $obs_video_threads_count;
	public $obs_graphics_threads = array();
	public $obs_graphics_threads_count;
	public $issues = array();

	public function __construct($raw_string = null)
	{
		if (isset($raw_string)) {
			$this->raw_string = $raw_string;
			$this->process();
		}
	}

	public function process()
	{
		if (!isset($this->raw_string)) {
			return false;
		}
		$profiler_lines = array();
		$thread_lines = array();

		// make objects of each valid line
		$stream = fopen('php://temp','rb+');
		fwrite($stream, $this->raw_string);
		rewind($stream);
		while (!feof($stream)) {
			$line = fgetss($stream);
			$obs_log_line = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($line);

			if ($obs_log_line->is_valid()) {
				$profiler_lines[] = $obs_log_line;
				if ($obs_log_line->is_type_init()) {
					$this->add_init_data_item($obs_log_line->name, $obs_log_line->get_run_time(), $obs_log_line->item_info);
				} elseif ($obs_log_line->is_type_thread()) {
					$thread_lines[] = $obs_log_line;
				}
			}
			unset($obs_log_line);
		}
		fclose($stream);

		// process the objects further
		$previous = null;
		$parent = null;
		foreach ($profiler_lines as $line) {
			if ($previous === null && $parent === null) {
				// get the run_program_init line object
				$this->run_program_init = $line;
			}
			if ($line->indent_level === 0) {
				$line->has_parent = false;
			} elseif ($line->indent_level > 0) {
				$line->has_parent = true;
				if ($previous->indent_level < $line->indent_level) {
					$line->parent_line = $previous;
					$parent = $previous;
				} elseif ($previous->indent_level === $line->indent_level) {
					$parent->add_child($line);
				}
			}

			$previous = $line;
		}

		$this->profiler_lines = $profiler_lines;
		$this->thread_lines = $thread_lines;

		// before OBS 20?
		$this->obs_video_threads = $this->find_obs_video_threads();
		$this->obs_video_threads_count = count($this->obs_video_threads);

		// OBS 20+
		$this->obs_graphics_threads = $this->find_obs_graphics_threads();
		$this->obs_graphics_threads_count = count($this->obs_graphics_threads);

		return $profiler_lines;
	}

	// this makes sense for init data, but not threads...
	//public function add_data_item($item_name, $item_value, $item_type, $item_info = null)
	public function add_init_data_item($item_name, $item_value, $item_info = null)
	{
		$item = array();
		$item['name'] = $item_name;
		$item['info'] = $item_info;
		$item['value'] = $item_value;
		//$this->data[$item_type][] = $item;
		$this->init_data[] = $item;
	}

	public function get_init_data()
	{
		/*
		$hotkey_thread_pos = strpos($this->raw_string, 'obs_hotkey_thread(');
		$hotkey_thread_raw = substr($this->raw_string, $hotkey_thread_pos);
		*/
		//return $this->data['init'];
		return $this->init_data;
	}

	public function get_thread_items()
	{
		//return $this->data['thread'];
		return $this->thread_lines;
	}

	public function find_obs_video_threads()
	{
		$all_thread_items = $this->get_thread_items();
		$obs_video_threads = array();
		foreach ($all_thread_items as $item) {
			if ($item->name === 'obs_video_thread') {
				$obs_video_threads[] = $item;
			}
		}
		return $obs_video_threads;
	}

	public function find_obs_graphics_threads()
	{
		$all_thread_items = $this->get_thread_items();
		$obs_graphics_threads = array();
		foreach ($all_thread_items as $item) {
			if ($item->name === 'obs_graphics_thread') {
				$obs_graphics_threads[] = $item;
			}
		}
		return $obs_graphics_threads;
	}

	public function flag_issues()
	{
		if (isset($this->thread_lines)) {
			$lines = $this->thread_lines;
		} else {
			return null;
		}
		foreach ($this->thread_lines as $line) {
			if (isset($line->percent_below_threshold) && !empty($line->percent_below_threshold)) {
				$percent = $line->get_percent_below_threshold();
				// If percent below threshold is listed at 100, continue to next item
				if ($percent === "100")
					continue;

				$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();

				if ($percent < 50) {
					$issue->severity = 'Critical';
				} elseif ($percent < 90) {
					$issue->severity = 'Major';
				} elseif ($percent < 100) {
					$issue->severity = 'Minor';
				}
				$issue->short_name = $line->name . " can't stay under " . $line->item_info . " threshold";
				if ($line->name === 'obs_hotkey_thread') {
					$issue->long_text = "Percentage below threshold: $percent";
				} elseif ($line->name === 'obs_video_thread') {
					$issue->long_text = "Percentage below threshold: $percent";
				}
				$issue->long_text = "This thread sometimes takes longer to complete than its allotted time allows.  It stays under its time limit $percent% of the time.";
				$issue->proposal = 'Reduce OBS resolution or FPS, reduce game settings (if playing a game)';
				$this->issues[] = $issue;

				unset($issue);
			}
		}

		return $this->issues;
	}
}
