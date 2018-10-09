<?php

namespace RytoEX\OBS\LogAnalyzer\Log;

// maybe make this a factory instead?
class Generic
{
	public $file_path;
	public $raw_string;
	public $raw_string_original;
	public $info = array();
	public $log_type;
	public $obs_version; // classic, studio
	public $real_obs_log_object = null;

	public function __construct($obs_log = null)
	{
		// check if strlen is greater than certain path_max-ish length
		// to see if it's a file/path?
		// 255, 260 (Windows), 1024, 4096
		// then load_from_file or load_from_string
		if (isset($obs_log)) {
			if (strlen($obs_log) > 510) {
				// 510 is the max length of two Linux path components (254) and two directory separators
				// assume this is string contents of log file
				$this->load_from_string($obs_log);
			} else {
				// assume this is a file path
				$this->load_from_file($obs_log);
			}
		}
	}

	public function load_from_file($file_path)
	{
		$this->file_path = $file_path;
		$raw_string = file_get_contents($this->file_path);
		$this->load_from_string($raw_string);
	}

	public function load_from_string($raw_string)
	{
		$this->raw_string = $raw_string;
		$this->raw_string_original = $raw_string;
	}

	public static function fromFile($file_path)
	{
		return new self($file_path);
	}

	public static function fromString($string)
	{
		return new self($string);
	}

	/* preprocess
	 *
	 * Find first OBS Studio timestamp and remove everything before it.
	 * This helps with detecting what type of log has been submitted.
	 */
	public function preprocess($raw_string)
	{
		$string = preg_replace('/.*?^(\d\d:\d\d:\d\d\.\d\d\d: )/ms', '$1', $raw_string, 1);

		return $string;
	}

	public function process()
	{
		if (!isset($this->raw_string)) {
			$this->info = array(
				'have_log' => false,
				'status_msg' => 'No log data'
			);
			return false;
		}

		$this->info = array(
			'have_log' => true,
			'status_msg' => 'Has log data'
		);

		$this->raw_string = $this->preprocess($this->raw_string);

		$this->log_type = $this->detect_log_type();
		$this->obs_version = $this->detect_obs_version();
		if ($this->obs_version !== "studio") {
			$this->info['status_msg'] = 'Log is malformed or from an older/unsupported version of OBS.';
			return false;
		}

		return true;
	}

	public function detect_log_type()
	{
		$pos = strpos($this->raw_string, ': ');
		if ($pos === 8) {
			// '16:04:38: CLR host plugin strings not found, dynamically loading 4 strings'
			return 'classic';
		} elseif ($pos === 12) {
			// '09:18:49.070: '
			// '11:55:36 PM.232:' - sometimes Windows, mostly *nix?
			return 'studio';
		} elseif ($pos === 19) {
			// 'Unhandled exception: c0000005'
			return 'studio-crash-win';
		} elseif ($pos === 7) {
			// 'Process:               obs [7317]'
			return 'studio-crash-mac';
		} else {
			return "invalid";
			// some linux debug logs don't have timestamps, but start with ' Attempted path: ' or 'info: '
			// - 'Attempted path: ' (strpos 14)
			// - ' Attempted path: ' (strpos 15) if the user accidentally copies a leading space
			// - 'info: ' (strpos 4)
			// older Studio logs (0.6.1? 0.10?) have a shorter timestamp, so they fall into this group
			// - '08:04:08 PM: ' (strpos 11)
		}
	}

	public function detect_obs_version()
	{
		if (isset($this->log_type)) {
			$array = explode('-', $this->log_type);
			return $array[0];
		}
	}

	public function is_log_from_classic()
	{
		if (isset($this->obs_version) && $this->obs_version === "classic")
			return true;

		return false;
	}

	public function is_log_from_studio()
	{
		if (isset($this->obs_version) && $this->obs_version === "studio")
			return true;

		return false;
	}

	public function is_a_crash_log()
	{
		if (isset($this->log_type) && strpos($this->log_type, "crash") !== false)
			return true;

		return false;
	}

	public function make_log_object()
	{
		if (!isset($this->log_type)) {
			$this->log_type = $this->detect_log_type();
		}
		if (!isset($this->obs_version)) {
			$this->obs_version = $this->detect_obs_version();
		}

		if ($this->is_log_from_studio()) {
			// Studio
			if ($this->is_a_crash_log()) {
				$this->real_obs_log_object =
					new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioCrashLog($this->raw_string_original,
					$this->log_type, false);
			} else {
				$this->real_obs_log_object =
					new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog($this->raw_string_original, false);
			}
		} elseif ($this->is_log_from_classic()) {
			// Classic
			// No plans to implement this.
			// Maybe still give a response for this method?
			$this->info['status_msg'] = 'The log appears to be from OBS Classic, which is no longer supported.  Please upgrade to OBS Studio.';
		} else {
			// File is unrecognized as a valid log
			$this->info['status_msg'] = 'The log is malformed, or it is from an older/unsupported version of OBS, or the log type is not supported.';
		}

		return $this->real_obs_log_object;
	}

	public function get_status_message()
	{
		if (!isset($this->info['status_msg'])) {
			$this->info['status_msg'] = 'Status unknown';
		}
		return $this->info['status_msg'];
	}
}
