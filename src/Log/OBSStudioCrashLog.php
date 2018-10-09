<?php

namespace RytoEX\OBS\LogAnalyzer\Log;

class OBSStudioCrashLog
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;

	public $raw_string = null;
	public $file_path;

	// 'studio-crash-win', 'studio-crash-mac'
	public $log_type;

	// for keeping '<unknown>' intact in crash logs
	public $allowable_html_tags = '<unknown>';

	// basic info
	public $exception_info = array();
	public $date_time;
	public $fault_address;
	public $libobs_info = array();
	public $os_info = array();
	public $cpu_info = array();

	// crash info
	public $crashed_thread_id;
	public $crashed_thread_stack_string;
	public $crashed_thread_stack;

	// log issues
	public $issues = array();

	public function __construct($crash_log = null, $type = 'unknown', $process = true)
	{
		if (isset($crash_log)) {
			if (strlen($crash_log) > 510) {
				// 510 is the max length of two Linux path components (254) and two directory
				// separators
				// assume this is string contents of log file
				$this->load_from_string($crash_log);
			} else {
				// assume this is a file path
				$this->load_from_file($crash_log);
			}

			// set type
			$this->log_type = $type;

			if ($process === true) {
				// automatically process the log by default,
				// but allow this to be deferred and called manually in case you want the
				// result of process_log()
				$this->process_log();
			}
		}
	}

	public function load_from_file($file_path = null)
	{
		if (isset($file_path)) {
			$this->file_path = $file_path;
			$this->load_from_string(file_get_contents($this->file_path));
		} elseif (isset($this->file_path)) {
			$this->load_from_string(file_get_contents($this->file_path));
		}
	}

	public function load_from_string($raw_string = null)
	{
		if (isset($raw_string)) {
			$this->raw_string = $raw_string;
		}
	}

	public function process_log()
	{
		// Stop early if there is no log string
		if (!isset($this->raw_string)) {
			return false;
		}

		// customize process for each OS
		// might be better to create separate classes for Win and Mac
		// detect log type first, if we have to
		if ($this->log_type === 'unknown') {
			$this->log_type = $this->detect_log_type();
		}

		// run OS-specific process functions
		if ($this->log_type === 'studio-crash-win') {
			$this->process_win();
		} elseif ($this->log_type === 'studio-crash-mac') {
			// not yet implemented
		}

		return true;
	}

	public function process_win()
	{
		// find basic info
		$this->exception_info = $this->find_exception_info();
		$this->date_time = $this->find_datetime();
		$this->fault_address = $this->find_fault_address();
		$this->libobs_info = $this->find_libobs_version();
		$this->os_info = $this->find_windows_version_info();
		$this->cpu_info = $this->find_cpu_info();

		// detect crashed thread stack
		$this->crashed_thread_id = $this->find_crashed_thread_id();
		$this->crashed_thread_stack_string = $this->find_crashed_thread_stack_string();
		$this->crashed_thread_stack = $this->find_crashed_thread_stack($this->crashed_thread_stack_string);

		// Try to flag issues based on collected log info
		$this->flag_issues();
	}

	public function detect_log_type()
	{
		$pos = strpos($this->raw_string, ': ');
		if ($pos === 19) {
			// 'Unhandled exception: c0000005'
			return 'studio-crash-win';
		} elseif ($pos === 7) {
			// 'Process:               obs [7317]'
			return 'studio-crash-mac';
		} else {
			return 'unknown';
		}
	}

	public function find_exception_info()
	{
		$exception = array();
		$exception['codes'] = array(
			$this->find_string($this->raw_string, 'Unhandled exception: '));
		return $exception;
	}

	public function find_datetime()
	{
		return $this->find_string($this->raw_string, 'Date/Time: ');
	}

	public function find_fault_address()
	{
		return $this->find_string($this->raw_string, 'Fault address: ');
	}

	public function find_libobs_version()
	{
		$version = $this->find_string($this->raw_string, 'libobs version: ');
		$libobs_info = ['version' => $version];
		return $libobs_info;
	}

	public function find_windows_version_info()
	{
		$os_info_line = $this->find_string($this->raw_string, 'Windows version: ');

		$version = $this->find_string($os_info_line, null, ' build');
		$build = $this->find_string($os_info_line, ' build ', ' (');
		$revision = $this->find_string($os_info_line, ' (revision: ', ';');
		$bitness = $this->find_string($os_info_line, '; ', '-bit');
		$os_info = ['version' => $version,
			'build' => $build,
			'revision' => $revision,
			'bitness' => $bitness];

		return $os_info;
	}

	public function find_cpu_info()
	{
		return $this->find_string($this->raw_string, 'CPU: ');
	}

	public function find_crashed_thread_id()
	{
		// windows
		$crashed_thread = $this->find_string($this->raw_string, 'Thread ', ' (Crashed)');

		return $crashed_thread;
	}

	public function find_crashed_thread_stack_string($thread_id = null)
	{
		if (!isset($this->crashed_thread_id)) {
			$this->crashed_thread_id = $this->find_crashed_thread_id();
		}
		if (!isset($thread_id)) {
			$thread_id = $this->crashed_thread_id;
		}
		$crashed_thread_marker = "Thread $thread_id (Crashed)";
		$crashed_thread_stack_string = $this->find_string($this->raw_string,
			$crashed_thread_marker, 'Thread ');

		$crashed_thread_stack_string = $crashed_thread_marker . "\n" . $crashed_thread_stack_string;
		return $crashed_thread_stack_string;
	}

	public function find_crashed_thread_stack($stack_string = null)
	{
		if (!isset($this->crashed_thread_stack_string)) {
			$this->crashed_thread_stack_string = $this->crashed_thread_stack_string();
		}
		if (!isset($stack_string)) {
			$stack_string = $this->crashed_thread_stack_string;
		}

		$stack = array();
		$stack_item_template = array('Stack', 'EIP', 'Arg0', 'Arg1', 'Arg2', 'Arg3', 'Address');
		$opts = array('special_end_marker' => 'EOF');
		$string = $this->find_string($stack_string, 'Address', null, $opts);

		// build the stack array line-by-line
		$stream = fopen('php://temp','rb+');
		fwrite($stream, $string);
		rewind($stream);
		while (!feof($stream)) {
			$line = fgetss($stream, 4096, $this->allowable_html_tags);
			$array = explode(' ', $line);
			$array[6] = rtrim($array[6]);

			$stack[] = array_combine($stack_item_template, $array);
		}
		fclose($stream);

		return $stack;
	}


	/* getter functions */
	public function get_os_version_string()
	{
		if (!isset($this->os_info)) {
			return '';
		}

		// 10.0 build 15063 (revision: 674; 64-bit)
		return $this->os_info['version'] .
			' build ' . $this->os_info['build'] .
			' (revision: ' . $this->os_info['revision'] .
			'; ' . $this->os_info['bitness'] . '-bit)';
	}

	public function get_crashed_thread_stack_string($html_safe = false)
	{
		if ($html_safe) {
			$string = strip_tags($this->crashed_thread_stack_string, $this->allowable_html_tags);
			$string = str_replace('<', '&lt;', $string);
		} else {
			$string = $this->crashed_thread_stack_string;
		}

		return $string;
	}


	/* analyzer functions */
	// issue_317
	// https://github.com/Xaymar/obs-studio_amf-encoder-plugin/issues/317
	public function has_amf_intel_opencl_crash()
	{
		return strpos($this->fault_address, 'igdrclneo64.dll') !== false
			&& strpos($this->crashed_thread_stack_string, 'enc-amf.dll!Plugin::AMD::Encoder::Start') !== false
			&& strpos($this->crashed_thread_stack_string, 'amfrt64.dll') !== false
			&& strpos($this->crashed_thread_stack_string, 'igdrclneo64.dll') !== false;
	}

	public function has_amd_driver_generic_crash()
	{
		return strpos($this->fault_address, 'atidxx64.dll') !== false
			&& strpos($this->crashed_thread_stack_string, 'atidxx64.dll') !== false
			&& strpos($this->crashed_thread_stack_string, 'atiumd6a.dll') !== false;
	}

	/* crash on AMF plugin load
	 */
	public function has_amf_plugin_startup_crash()
	{
		return strpos($this->crashed_thread_stack_string, 'enc-amf.dll') !== false
			&& strpos($this->crashed_thread_stack_string, 'amfrt64.dll') !== false
			&& strpos($this->crashed_thread_stack_string, 'obs.dll!obs_init_module') !== false;
	}

	public function has_amf_generic_crash()
	{
		return strpos($this->crashed_thread_stack_string, 'enc-amf.dll') !== false
			&& strpos($this->crashed_thread_stack_string, 'amfrt64.dll') !== false;
	}

	public function has_vst_crash()
	{
		return strpos($this->crashed_thread_stack_string, 'obs-vst.dll') !== false;
	}

	/* Crash caused by Dell Backup and Recovery software
	 * 
	 */
	public function has_dell_backup_and_recovery_crash()
	{
		return strpos($this->crashed_thread_stack_string, 'dbroverlayiconbackuped.dll') !== false;
	}

	/* Crash caused by Personify
	 */
	public function has_personify_crash()
	{
		return strpos($this->crashed_thread_stack_string, 'personifycameoue.ax') !== false;
	}


	/* return JSON summary/results
	 */
	public function get_json_result()
	{
		
	}


	/* flag_issues
	 * 
	 */
	public function flag_issues()
	{
		$issue_handler = new \RytoEX\OBS\LogAnalyzer\Issue\Handler($this);
		$issue_handler->run_crash_rules();
		$this->issues = array_merge($this->issues, $issue_handler->issues);
	}
}
