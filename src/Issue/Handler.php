<?php

namespace RytoEX\OBS\LogAnalyzer\Issue;

class Handler
{
	public static $issue_count = 0;
	public $issues = array();
	public $obs_log_object;

	public function __construct($obs_log_object)
	{
		$this->obs_log_object = $obs_log_object;
	}

	public function add_issue($issue)
	{
		$this->issues[] = $issue;
		self::$issue_count++;
	}

	public function add_issues(array $issues)
	{
		// re-index the array
		$new_array = array_values($issues);
		$this->issues = array_merge($this->issues, $new_array);
		self::$issue_count += count($new_array);
	}

	public function run_rules()
	{
		// recursively include_once(__DIR__ . /*.inc) files?
		// recursively include_once *.inc files from user specified directory?
		include(__DIR__ . '/base_rules/base_rules.php.inc');
	}

	public function run_crash_rules()
	{
		include(__DIR__ . '/base_rules/base_crash_rules.php.inc');
	}
}
