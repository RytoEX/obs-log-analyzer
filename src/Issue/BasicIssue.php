<?php

namespace RytoEX\OBS\LogAnalyzer\Issue;

class BasicIssue
{
	public static $issues_created = 0;
	public $id;
	public $severity; // info, warning, minor, major, critical
	//public $severity_num; // numeric representation of severity?
	public $short_name;
	public $long_text;
	public $proposal;
	public $steps; // array of resolution steps
	public $tags;

	// global allowed HTML tags for strip_tags
	private $allowable_html_tags;

	public function __construct($severity = null, $short_name = null,
		$long_text = null, $proposal = null, $tags = null)
	{
		self::$issues_created++;
		$this->id = self::$issues_created;
		// Could check and set these individually if there's a point where we want to create an issue with partial info.
		if (isset($severity, $short_name, $long_text, $proposal)) {
			$this->severity = $severity;
			$this->short_name = $short_name;
			$this->long_text = $long_text;
			$this->proposal = $proposal;
		}
		$this->tags = $tags;
	}

	public function get_severity($strip_html = false)
	{
		if ($strip_html) {
			return strip_tags($this->severity);
		}
		return $this->severity;
	}

	public function get_short_name($strip_html = false)
	{
		if ($strip_html) {
			return strip_tags($this->short_name);
		}
		return $this->short_name;
	}

	public function get_long_text($strip_html = false)
	{
		if ($strip_html) {
			return strip_tags($this->long_text);
		}
		return $this->long_text;
	}

	public function get_proposal($strip_html = false)
	{
		if ($strip_html) {
			return strip_tags($this->proposal);
		}
		return $this->proposal;
	}

	public function get_steps($strip_html = false)
	{
		if (!isset($this->steps)) {
			return '';
		}

		$steps = '';
		$num = 1;
		foreach($this->steps as $step) {
			$steps .= $num++ . ". $step\n";
		}

		$steps = rtrim($steps);
		if ($strip_html) {
			$steps = strip_tags($steps);
		}

		return $steps;
	}

	public function get_steps_ol($strip_html = false)
	{
		if (!isset($this->steps)) {
			return '';
		}

		$steps = "<ol>\n";
		$num = 1;
		foreach($this->steps as $step) {
			$steps .= "<li>" . $num++ . ". $step</li>\n";
		}
		$steps .= "</ol>\n";

		if ($strip_html) {
			$allowable_tags = $this->allowable_html_tags . '<ol><li>';
			$steps = strip_tags($steps);
		}

		return $steps;
	}

	public function get_tags($strip_html = false)
	{
		if ($strip_html) {
			return strip_tags($this->tags);
		}
		return $this->tags;
	}

	public function get_all_output($strip_html = false)
	{	// maybe an option to get without labels?
		$output = "";
		$output .= "ID: " . $this->id . "\n";
		$output .= "Severity: " . $this->get_severity($strip_html) . "\n";
		$output .= "Issue: " . $this->get_short_name($strip_html) . "\n";
		$output .= "Description: " . $this->get_long_text($strip_html) . "\n";
		$output .= "Proposal: " . $this->get_proposal($strip_html) . "\n";
		$output .= "Steps: \n" . $this->get_steps($strip_html) . "\n";
		return $output;
	}

	// @todo: refactor calls to this to use get_issues_created() ?
	public function get_count()
	{
		return self::$issues_created;
	}

	public function get_issues_created()
	{
		return self::$issues_created;
	}
}
