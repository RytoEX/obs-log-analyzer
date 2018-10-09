<?php

namespace RytoEX\OBS\LogAnalyzer\Log;

class OBSStudioLog
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;


	/* can maybe write update scripts for Windows 7 and 8.1 by grabbing the latest update history page
	 * and parsing the CSV file for the following...
	 * Win7:    "Kernel32.dll","6.1.7601.24060"
	 *  - capture the last digit group
	 * Win8.1:  "(6\.3\.9600\.(\d+))"
	 *  - capture all groups, sort last digit group for highest number
	 */
	const LATEST_WINDOWS_81_VERSION = array(
		'version' => '6.3',
		'build' => '9600',
		'revision' => '18955',
		'bitness' => '64',
		'ms_version_name' => '',
		'ms_codename' => 'Blue');
	const LATEST_WINDOWS_8_VERSION = array(
		'version' => '6.2',
		'build' => '9200',
		'revision' => '',
		'bitness' => '64',
		'ms_version_name' => '',
		'ms_codename' => 'Windows 8');
	// win7 seems to have separate revision numbers for x86 vs x64
	const LATEST_WINDOWS_7_VERSION = array(
		'version' => '6.1',
		'build' => '7601',
		'revision' => '24117',
		'bitness' => '64',
		'ms_version_name' => '',
		'ms_codename' => 'Vienna');
	const LATEST_OBS_VERSION = '21.1.0'; // really 21.1.1, but only for macOS
	const LATEST_OBS_VERSIONS = array(
		'win' => '21.1.0',
		'mac' => '21.1.1',
		'nix' => '21.1.0');
	const LATEST_BROWSER_SOURCE_VERSION = '1.31.0';
	const LATEST_BROWSER_SOURCE_VERSIONS = array(
		'win' => '1.31.0',
		'mac' => '1.31.0');
	const LATEST_AMF_ENCODER_VERSION = '2.3.3.0';

	// D3D_FEATURE_LEVEL
	const D3D_FEATURE_LEVEL_10_1 = '41216';
	const D3D_FEATURE_LEVEL_11_0 = '45056';

	// @todo: change these from consts here to properties in module classes
	// .dll for Windows, .so for Mac/Linux
	const BROWSER_SOURCE_DLL = 'obs-browser.dll';
	const AMF_PLUGIN_DLL = 'enc-amf.dll';
	// Not sure these even exist...
	const BROWSER_SOURCE_SO = 'obs-browser.so';
	const AMF_PLUGIN_SO = 'enc-amf.so';

	public $file_path;
	public $file_size;
	public $file_resource;

	// OS version update info
	/* @todo: Perhaps rework into just one property, but all the classes used
	 * would have identical public function calls.
	 */
	public $win10info;
	public $macosinfo;
	public $os_version_info;

	// log data/properties
	public $is_valid; // bool
	public $is_complete; // bool
	public $is_clean; // bool
	public $raw_string = null;
	public $raw_string_full = null; // raw_string with uploader note at the top
	public $upload_datetime;
	public $startup_string;
	public $cpu_info = array();
	public $mem_info = array();
	public $os_info = array();
	public $run_as_admin;
	public $is_aero_enabled;
	public $aero_status;
	public $portable_mode;
	public $obs_version_info = array(); // rawstring, versionnum, bitness, os
	public $startup_pos;
	public $audio_setting_resets; // if > 1, not a clean log
	public $video_setting_resets; // if > 1, not a clean log
	public $audio_settings = array();
	public $video_settings = array();
	public $renderer; // ': OpenGL version: '
	public $renderer_info = array(); // D3D11: Adapter, Feature Level; OpenGL: Version, Driver?
	public $video_adapters = array();
	public $has_coreaudio; // ': [CoreAudio encoder]: CoreAudio' - didn't load // ': [CoreAudio encoder]: Adding' - loaded
	public $has_amf_support;
	public $has_nvenc_support;
	public $has_browser_source;
	public $has_blackmagic_support;
	public $loaded_modules_string;
	public $loaded_modules;
	public $loaded_module_files = array();
	public $loaded_module_objects = array();
	public $browser_source_module; // these version numbers are easy to check and can fall behind
	public $amf_encoder_module;
	public $scene_collections = array(); // array of objects
	public $recording_sessions;
	public $recording_session_starts;
	public $recording_session_stops;
	public $has_incomplete_recording_session;
	public $streaming_sessions;
	public $streaming_session_starts;
	public $streaming_session_stops;
	public $has_incomplete_streaming_session;
	public $has_max_audio_buffering;
	public $sources = array();
	public $has_profiler;
	public $profiler_pos;
	public $profiler_string;
	public $profiler_obj = null;
	public $memory_leaks;
	public $issues = array();

	public function __construct($obs_log = null, $process = true)
	{
		if (isset($obs_log)) {
			if (strlen($obs_log) > 510) {
				// 510 is the max length of two Linux path components (254) and two directory separators
				// assume this is string contents of log file
				$this->load_from_string($obs_log);
			} else {
				// assume this is a file path
				$this->load_from_file($obs_log);
			}
			if ($process === true) {
				// automatically process the log by default,
				// but allow this to be deferred and called manually in case you want the result of process_log()
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
		//self::load_from_string($this->raw_string);
	}

	public function load_from_string($raw_string = null)
	{
		if (isset($raw_string)) {
			$this->raw_string = $raw_string;
		}
	}

	// functions to load OS version info
	public function load_all_os_version_info()
	{
		$this->load_win10_version_info();
		$this->load_macos_version_info;
	}

	public function load_win10_version_info()
	{
		$this->win10info = new \RytoEX\OBS\LogAnalyzer\Config\Windows10Info(true, false, false);
	}

	public function load_macos_version_info()
	{
		$this->macosinfo = new \RytoEX\OBS\LogAnalyzer\Config\MacOSInfo(true, false, false);
	}

	public function is_complete()
	{
		if (isset($this->is_complete)) {
			return $this->is_complete;
		}
		// could try to set has_profiler and memory_leaks if not set?
		//if (isset($this->has_profiler) && $this->has_profiler === false) {
		if ($this->has_profiler() === false) {
			return false;
		}
		if (!isset($this->memory_leaks)) {
			return false;
		}
		return true;
	}

	public function is_clean()
	{
		// no changes to audio/video settings
		// up to 1 recording session and up to 1 streaming session
		// no incomplete recording/streaming sessions
		// no scene collection switches?
		// no scene switches?
		// has profiler results?
		if (isset($this->is_clean)) {
			return $this->is_clean;
		} elseif (isset($this->audio_setting_resets) && $this->audio_setting_resets > 1) {
			return false;
		} elseif (isset($this->video_setting_resets) && $this->video_setting_resets > 1) {
			return false;
		} elseif ($this->has_incomplete_recording_session()) {
			return false;
		} elseif ($this->has_incomplete_streaming_session()) {
			return false;
		} elseif ($this->recording_session_starts > 1) {
			return false;
		} elseif ($this->streaming_session_starts > 1) {
			return false;
		} else {
			return true;
		}
	}

	public function validate_log()
	{
		// what steps can we take to see if this is a valid log before processing?
		// check for strpos of some common strings?
		// ': OBS '
		// ': ---------------------------------'
		// ': audio settings reset:'
		// ': video settings reset:'
		// ':   Loaded Modules:' // OBS 0.15.3+?
		// ': Loading module: ' // OBS 0.15.2-?
		// ': ==== Startup complete ==============================================='
		// ': All scene data cleared'
		// ': ==== Shutting down =================================================='
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$needles = [
			': OBS ',
			': audio settings reset:',
			': video settings reset:',
			//':   Loaded Modules:', probably shouldn't fail on this, but should warn to upgrade
			': ==== Startup complete ===============================================',
			': All scene data cleared']; // OBS Studio 0.11.0 or older?
		foreach ($needles as $needle) {
			$pos = strpos($haystack, $needle);
			if ($pos === false) {
				return false;
			}
		}
		return true;
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

	public function process_log()
	{
		// Stop early if there is no log string
		if (!isset($this->raw_string)) {
			return false;
		}

		// Preprocess the raw_string
		$this->raw_string_full = $this->raw_string;
		$this->raw_string = $this->preprocess($this->raw_string);

		// Validate the log string
		$this->is_valid = $this->validate_log();
		if ($this->is_valid === false) {
			$invalid_log = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
			$invalid_log->severity = 'Critical';
			$invalid_log->short_name = 'OBS version is too old';
			$invalid_log->long_text = 'Your OBS log is from a version of OBS that is not supported by this analyzer.';
			$invalid_log->proposal = 'Ensure that you are running the latest version of OBS.';
			$this->issues[] = $invalid_log; // clone the object instead?
			return false;
		}

		// Check if the log has profiler data
		$this->has_profiler = $this->has_profiler();

		// If the log has profiler data, load a Profiler object and process it
		if ($this->has_profiler()) {
			$this->profiler_string = $this->find_profiler_string();
			$this->profiler_obj = new \RytoEX\OBS\LogAnalyzer\Profiler\Profiler($this->profiler_string);
		}

		// Check if the log has memory leaks
		$this->memory_leaks = $this->find_memory_leaks();

		// Check if the log is "complete"
		$this->is_complete = $this->is_complete();

		// Get the upload datetime, if it was uploaded via OBS
		// This should be the only time we need $this->raw_string_full
		$this->upload_datetime = $this->find_upload_datetime($this->raw_string_full);

		// Get the system and startup info from the top of the log
		$this->startup_string = $this->find_startup_string();
		$this->cpu_info = $this->find_cpu_info();
		$this->mem_info = $this->find_memory_info();
		$this->os_info = $this->find_os_info();
		$this->run_as_admin = $this->find_run_as_admin();
		if ($this->is_os_windows()) {
			// We probably don't really need this check, as find_aero should return false otherwise.
			$this->aero_status = $this->find_aero();

			// @todo: check win10 gaming
			// @todo: check security software status
			// @todo: load Windows version info for specific Windows version
			$this->load_win10_version_info();
		}
		if ($this->is_os_mac()) {
			$this->load_macos_version_info();
		}
		$this->portable_mode = $this->find_portable_mode();
		$this->obs_version_info = $this->find_obs_version_info();
		$this->add_audio_settings($this->find_audio_settings($this->startup_string));
		$this->renderer = $this->find_renderer();
		$this->add_video_settings($this->find_video_settings($this->startup_string));

		// If the renderer is D3D11, find the renderer and video adapter info.
		if ($this->is_renderer_d3d11()) {
			$this->renderer_info['adapter'] = $this->find_d3d11_adapter();
			$this->renderer_info['feature_level'] = $this->find_d3d11_feature_level();

			// If the renderer adapter isn't the MS Basic Driver, try to find video adapters.
			if ($this->get_renderer_adapter() !== "Microsoft Basic Render Driver") {
				$this->video_adapters = $this->find_video_adapters();
			}
		} elseif ($this->is_renderer_opengl()) {
			$opengl_load_string = $this->find_opengl_load_string();
			$this->renderer_info['adapter'] = $this->find_opengl_adapter($opengl_load_string);
			$this->renderer_info['version'] = $this->find_opengl_version($opengl_load_string);
			$opengl_vendor_marker = ', version ' . $this->renderer_info['version'] . ' ';
			$this->renderer_info['vendor_info'] = $this->find_opengl_vendor_info($opengl_load_string, $opengl_vendor_marker);
			$this->renderer_info['shader_version'] = $this->find_opengl_shader_version($opengl_load_string);
		}

		// Check module startup output
		$this->has_coreaudio = $this->has_coreaudio();
		$this->has_amf_support = $this->has_amf_support();
		$this->has_nvenc_support = $this->has_nvenc_support();
		$this->has_browser_source = $this->has_browser_source();
		$this->has_vlc = $this->has_vlc();
		$this->has_blackmagic_support = $this->has_blackmagic_support();

		// Check loaded modules
		$this->loaded_modules_string = $this->find_loaded_modules_string();
		$this->loaded_modules = $this->find_loaded_modules($this->loaded_modules_string);
		if ($this->has_browser_source() && 
			(in_array(self::BROWSER_SOURCE_DLL, $this->loaded_modules)
			|| in_array(self::BROWSER_SOURCE_SO, $this->loaded_modules))) {
			$browser_module = new \RytoEX\OBS\LogAnalyzer\Module\OBSModule();
			$browser_module->module_name = 'browser_source';
			if ($this->is_os_windows()) {
				$browser_module->filename = self::BROWSER_SOURCE_DLL;
			} else {
				$browser_module->filename = self::BROWSER_SOURCE_SO;
			}
			$browser_module->raw_string = $this->find_browser_source_module_load_string();
			$browser_module->version = $this->find_browser_source_version($browser_module->raw_string);
			$this->loaded_module_objects[] = $browser_module;
			$this->browser_source_module = $browser_module;
		}
		if ($this->has_amf_support() && 
			(in_array(self::AMF_PLUGIN_DLL, $this->loaded_modules)
			|| in_array(self::AMF_PLUGIN_SO, $this->loaded_modules))) {
			$amf_plugin = new \RytoEX\OBS\LogAnalyzer\Module\AMF_Encoder($this->find_amf_encoder_module_load_string());
			if ($this->is_os_windows()) {
				$amf_plugin->filename = self::AMF_PLUGIN_DLL;
			} else {
				$amf_plugin->filename = self::AMF_PLUGIN_SO;
			}
			$this->loaded_module_objects[] = $amf_plugin;
			$this->amf_encoder_module = $amf_plugin;
		}

		// Check recording and streaming sessions
		$this->count_sessions();

		// Finally, check if the log is "clean"
		$this->is_clean = $this->is_clean();

		// @todo: get scene collection info
		// @todo: get source info

		// Try to flag issues based on collected log info
		$this->flag_issues();

		// Try to flag issues based on profiler data
		if ($this->has_profiler()) {
			$profiler_issues = $this->profiler_obj->flag_issues();
			$this->issues = array_merge($this->issues, $profiler_issues);
		}

		// You made it here, probably a success?
		return true;
	}

	public function has_profiler()
	{
		if (isset($this->has_profiler)) {
			return $this->has_profiler;
		}
		$profilerExists = $this->find_profiler_string_pos();

		if ($profilerExists !== false) {
			return true;
		} else {
			return false;
		}
	}

	public function find_upload_datetime($string)
	{
		return $this->find_string($string, ' log file uploaded at ');
	}

	public function find_cpu_info()
	{
		$cpu_name = $this->find_cpu_name();
		$cpu_speed = $this->find_cpu_speed();
		$cpu_core_counts = $this->find_cpu_cores();
		$cpu_info = ['name' => $cpu_name,
			'speed' => $cpu_speed,
			'cores' => $cpu_core_counts];
		return $cpu_info;
	}

	public function find_cpu_name()
	{
		$cpu_name_marker = ': CPU Name: ';
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$cpu_name = $this->find_string($haystack, $cpu_name_marker);

		return $cpu_name;
	}

	public function find_cpu_speed()
	{
		$cpu_speed_marker = ': CPU Speed: ';
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$cpu_speed = $this->find_string($haystack, $cpu_speed_marker);

		return $cpu_speed;
	}

	public function find_cpu_cores()
	{
		$cpu_phys_cores = $this->find_cpu_physical_cores();
		$cpu_log_cores = $this->find_cpu_logical_cores();
		$core_array = ['physical' => $cpu_phys_cores,
			'logical' => $cpu_log_cores];
		return $core_array;
	}

	public function find_cpu_physical_cores()
	{
		$cpu_phys_cores_marker = ': Physical Cores: ';
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$cpu_phys_cores = $this->find_string($haystack, $cpu_phys_cores_marker, ',');
		return $cpu_phys_cores;
	}

	public function find_cpu_logical_cores()
	{
		$cpu_log_cores_marker = ', Logical Cores: ';
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$cpu_log_cores = $this->find_string($haystack, $cpu_log_cores_marker);
		return $cpu_log_cores;
	}

	public function find_memory_info()
	{
		$mem_phys_total = $this->find_memory_physical_total();
		$mem_phys_free = $this->find_memory_physical_free();
		$mem_info = ['physical' => [
			'total' => $mem_phys_total,
			'free' => $mem_phys_free]
		];
		return $mem_info;
	}

	public function find_memory_physical_total()
	{
		$phys_mem_total_marker = ': Physical Memory: ';
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$phys_mem_total = $this->find_string($haystack, $phys_mem_total_marker, ' Total, ');

		if ($phys_mem_total === false) { // less strict check?
			$phys_mem_total = $this->find_string($haystack, $phys_mem_total_marker, ' Total');
		}
		return $phys_mem_total;
	}

	public function find_memory_physical_free()
	{
		$phys_mem_total = 'MB';
		if (isset($this->mem_info['physical']['total'])) {
			$phys_mem_total = ": Physical Memory: " . $this->mem_info['physical']['total'];
		}
		$phys_mem_free_marker = $phys_mem_total . ' Total, ';
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$phys_mem_free = $this->find_string($haystack, $phys_mem_free_marker, ' Free');
		return $phys_mem_free;
	}

	public function find_os_info()
	{
		$os_name = $this->find_os_name();
		$os_version = $this->find_os_version($os_name);
		$os_info = ['name' => $os_name,
			'version' => $os_version];
		return $os_info;
	}

	public function find_os_name()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		// assume Windows is most common
		$windows_pos = strpos($haystack, ': Windows Version: ');
		$os = 'Windows';
		if ($windows_pos === false) {
			$macos_pos = strpos($haystack, ': OS Name: Mac OS X');
			$os = 'Mac OS X';
			// assume Linux otherwise?
			if ($macos_pos === false) {
				$os = 'Linux';
			}
		}
		return $os;
	}

	public function find_os_version($os_name = null)
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		// version, build, revision, kernel
		if (isset($this->os_info['name']) && !isset($os_name)) {
			$os_name = $this->os_info['name'];
		} elseif (!isset($os_name)) {
			return false;
		}

		if ($os_name === 'Windows') {
			$version_info = $this->find_os_version_win();
		} elseif ($os_name === 'Mac OS X') {
			$version_info = $this->find_os_version_mac();
		} elseif ($os_name === 'Linux') {
			$version_info = $this->find_os_version_nix();
		}

		return $version_info;
	}

	public function find_os_version_win()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$version_marker = ': Windows Version: ';
		$version = $this->find_string($haystack, $version_marker, ' Build');
		$build_marker = ' Build ';
		$build = $this->find_string($haystack, $build_marker, ' (');
		$revision_marker = ' (revision: ';
		$revision = $this->find_string($haystack, $revision_marker, ';');
		$bitness_marker = '; ';
		$bitness = $this->find_string($haystack, $bitness_marker, '-bit');
		$version_info = ['version' => $version,
			'build' => $build,
			'revision' => $revision,
			'bitness' => $bitness];
		return $version_info;
	}

	public function find_os_version_mac()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$version_marker = ': OS Version: ';
		$version_string = $this->find_string($haystack, $version_marker, ' (');
		$build_marker = '(Build ';
		$build = $this->find_string($haystack, $build_marker, ')');
		$kernel_marker = ': Kernel Version: ';
		$kernel = $this->find_string($haystack, $kernel_marker);

		$version = $this->find_string($version_string, ' ');

		$version_info = ['version_string' => $version_string,
			'version' => $version,
			'build' => $build,
			'kernel' => $kernel];
		return $version_info;
	}

	public function find_os_version_nix()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$kernel_marker = ': Kernel Version: ';
		$kernel = $this->find_string($haystack, $kernel_marker);
		$dist_marker = ': Distribution: ';
		$dist = $this->find_string($haystack, $dist_marker);
		$version_info = ['kernel' => $kernel,
			'distribution' => $dist];
		return $version_info;
	}

	public function find_run_as_admin()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$admin_marker = ': Running as administrator: ';
		$admin = $this->find_string($haystack, $admin_marker);
		return $admin;
	}

	public function find_aero()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$aero_marker = ': Aero is ';
		$aero = $this->find_string($haystack, $aero_marker);
		if ($aero !== false) {
			if (substr($aero, -5) !== 'abled') {
				/* Windows 7:
				 * 04:44:48.695: Windows Version: 6.1 Build 7601 (revision: 23714; 64-bit)
				 * 04:44:48.695: Aero is Enabled
				 * 
				 * Windows 8+:
				 * 23:10:36.711: Windows Version: 10.0 Build 14393 (revision: 1066; 64-bit)
				 * 23:10:36.712: Aero is Enabled (Aero is always on for windows 8 and above)
				 */
				$aero = $this->find_string($haystack, $aero_marker, ' (');
			}
		} elseif ($aero === false) {
			// Aero is probably disabled or doesn't exist
			$aero = $this->find_string($haystack, $aero_marker);
		}
		return $aero;
	}

	public function find_portable_mode()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$portable_marker = ': Portable mode: ';
		$portable = $this->find_string($haystack, $portable_marker);
		return $portable;
	}

	// make way to quickly check if OBS is built from git
	// probably just strpos '-g'
	public function find_obs_version_info()
	{	// git: 18.0.1-29-g5845664a
		// https://git-scm.com/docs/git-describe
		// 18.0.1   - HEAD
		// 29       - number of commits on top of HEAD
		// g        - "git" identifier
		// 5845664a - commit short hash / abbreviated object name
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}
		$obs_version_info = ['version' => null,
			'bitness' => null,
			'os' => null];

		$obs_version_marker = ': OBS ';
		$obs_version_string = $this->find_string($haystack, $obs_version_marker);
		$version_num = substr($obs_version_string, 0, strpos($obs_version_string, ' ('));
		$obs_version_info = ['version' => $version_num];
		$bitness_pos = strpos($obs_version_string, 'bit');

		$os_build_marker = '(';
		if ($bitness_pos !== false) {
			$bitness = substr($obs_version_string, strpos($obs_version_string, '(')+1, 2);
			$obs_version_info['bitness'] = $bitness;
			$os_build_marker = 'bit, ';
		}

		// get OS-build identifier
		$os_pos = strpos($obs_version_string, $os_build_marker);
		if ($os_pos !== false) {
			$os = substr($obs_version_string, $os_pos + 1, -1);
			$obs_version_info['os'] = $os;
		}

		// if on Windows build and no bitness detected, assume 32-bit
		if ($obs_version_info['os'] === 'windows' && !isset($obs_version_info['bitness'])) {
			$obs_version_info['bitness'] = '32';
		}

		return $obs_version_info;
	}

	public function find_audio_settings($haystack, $offset = 0, $before = null)
	{
		// not sure if we need the $before pos arg
		// audio settings can appear in startup_string or in the regular log session before shutdown
		//if (isset($this->file_resource)
		$audio_settings_marker = ': audio settings reset:';
		$audio_settings_pos = strpos($haystack, $audio_settings_marker, $offset);
		if ($audio_settings_pos !== false) {
			$opts = array('offset' => $audio_settings_pos);
			$samples_per_sec = $this->find_string($haystack, ': 	samples per sec:', null, $opts);
			$speakers = $this->find_string($haystack, ': 	speakers:', null, $opts);
			$audio_settings = new \RytoEX\OBS\LogAnalyzer\Audio\Settings();
			$audio_settings->samples_per_sec = $samples_per_sec;
			$audio_settings->speakers = $speakers;
			return $audio_settings;
		}
		return false;
	}

	public function find_video_settings_string($haystack = null, $offset = 0)
	{
		if (!isset($haystack) && isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		// need to iterate a few times to make sure we have the right string
		// need to fetch to EOF the first time
		$marker = ': video settings reset:';
		$opts = array('offset' => $offset,
			'special_end_marker' => 'EOF');
		$string = $this->find_string($haystack, $marker, null, $opts);

		// format is the last video setting before OBS 21.0.3
		$format_marker = ': 	format:';
		$format_pos = strpos($string, $format_marker);
		$opts = array('end_marker_offset' => $format_pos + strlen($format_marker));

		// YUV mode is the last video setting from OBS 21.0.3 forward
		$yuv_marker = ': 	YUV mode:';
		$yuv_pos = strpos($string, $yuv_marker);
		if ($yuv_pos !== false)
			$opts = array('end_marker_offset' => $yuv_pos + strlen($yuv_marker));

		// find the next ': ' after the last setting field
		$string = $this->find_string($string, null, ': ', $opts);

		return $string;
	}

	public function find_video_settings($haystack = null, $offset = 0)
	{
		if (!isset($haystack) && isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$string = $this->find_video_settings_string($haystack);

		$video_settings = new \RytoEX\OBS\LogAnalyzer\Video\Settings($string);
		$video_settings->process();
		return $video_settings;
	}

	public function find_renderer()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$d3d11 = strpos($haystack, ': Initializing D3D11');
		if ($d3d11 === false) {
			// do we even need to try to find this string?
			//return 'OpenGL';
			$opengl = strpos($haystack, ': Initializing OpenGL'); // Yay!  PR was merged!  Search for this string as of 18.0.2 / 19.0.0.
			if ($opengl === false) {
				// try to find a more generic string in case this is an older OBS
				$opengl = strpos($haystack, 'OpenGL renderer');
				if ($opengl !== false) {
					return 'OpenGL';
				} else {
					return null;
				}
			} else {
				return 'OpenGL';
			}
		}

		return 'D3D11';
	}

	public function find_audio_monitoring_device($haystack = null)
	{
		if (isset($haystack)) {
			// just need to avoid the other branches if this is set
			// since I think audio monitoring devices appear in the log whenever it is changed
		} elseif (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$audio_monitor_marker = ': Audio monitoring device:';
		$audio_monitor_marker_pos = strpos($haystack, $audio_monitor_marker);
		if ($audio_monitor_marker_pos !== false) {
			$opts = array('offset' => $audio_monitor_marker_pos);
			$name = $this->find_string($haystack, ': 	name: ', null, $opts);
			$id = $this->find_string($haystack, ': 	id: ', null, $opts);
			$audio_monitor_device = array(
				'name' => $name,
				'id' => $id);
			return $audio_monitor_device;
		}
		return false;
		//return null; // return null instead?
	}

	public function find_video_adapters()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$adapters = false;
		$adapters_pos = strpos($haystack, ': Available Video Adapters:');
		if ($adapters_pos !== false) {
			$adapters = array();
			$adapters_end_marker = ': ---------------------------------';
			$adapters_end_pos = strpos($haystack, $adapters_end_marker, $adapters_pos);
			$adapters_str = substr($haystack, $adapters_pos, $adapters_end_pos - $adapters_pos + strlen($adapters_end_marker));

			$adapter_marker = ': 	Adapter ';
			$adapter_marker_pos = 0;
			$output_marker = ': 	  output ';

			//$output_marker_pos
			// might be better to fopen, fseek, fgetss and go line-by-line
			// because some adapters do not have outputs attached, and we don't want to accidentally
			// get an output for a different adapter
			// that or try to find the next adapter marker and rewrite find_string to accept an end limit
			// or set up substrings
			do {
				$adapter_marker_pos = strpos($adapters_str, $adapter_marker, $adapter_marker_pos+1);
				$next_adapter_marker_pos = strpos($adapters_str, $adapter_marker, $adapter_marker_pos+1);
				$adapter_end_pos = $next_adapter_marker_pos;
				if ($adapter_end_pos === false) {
					$adapter_end_marker = ': Loading up ';
					$adapter_end_marker_pos = strpos($adapters_str, $adapter_end_marker, $adapter_marker_pos);
					$adapter_end_pos = $adapter_end_marker_pos;
				}
				$adapter_str = substr($adapters_str, $adapter_marker_pos, $adapter_end_pos - $adapter_marker_pos);
				$adapter = new \RytoEX\OBS\LogAnalyzer\Video\Adapter();

				// Maybe move all of the stuff below to the video_adapter class?
				$adapter->number = $this->find_string($adapter_str, $adapter_marker, ': ');
				$adapter->name = $this->find_string($adapter_str, $adapter->number . ': ');
				$adapter->dedicated_vram = $this->find_string($adapter_str, ': 	  Dedicated VRAM: ');
				$adapter->shared_vram = $this->find_string($adapter_str, ': 	  Shared VRAM: ');
				//$output_marker_pos = $adapter_marker_pos;
				$output_marker_pos = 0;

				// maybe switch to a while loop instead for adapters that have no outputs?
				do {
					$output_marker_pos = strpos($adapter_str, $output_marker, $output_marker_pos + 1);
					$opts = array('offset' => $output_marker_pos);
					$output_str = $this->find_string($adapter_str, $output_marker, null, $opts);
					if (empty($output_str)) {
						break;
					}
					$output = new \RytoEX\OBS\LogAnalyzer\Video\Output($output_str);
					$adapter->add_output($output);
				} while (strpos($adapter_str, $output_marker, $output_marker_pos + 1) !== false);
				$adapters[] = $adapter;
			} while (strpos($adapters_str, $adapter_marker, $adapter_marker_pos + 1) !== false);
		}
		return $adapters;
	}

	// @todo: refactor to also find adapter index number
	public function find_d3d11_adapter()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$adapter = $this->find_string($haystack, ': Loading up D3D11 on adapter ', ' (');
		return $adapter;
	}

	public function find_d3d11_feature_level()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$feature_level = $this->find_string($haystack, ': D3D11 loaded successfully, feature level used: ');
		if ($feature_level === false) {
			// OBS Studio pre-18.0.2 had this typo'd line
			$feature_level = $this->find_string($haystack, ': D3D11 loaded sucessfully, feature level used: ');
		}

		return $feature_level;
	}

	public function find_opengl_adapter()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$adapter = $this->find_string($haystack, ': Loading up OpenGL on adapter ');

		return $adapter;
	}

	public function find_opengl_load_string()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$marker = ': OpenGL loaded successfully';
		$load_string = substr($marker . $this->find_string($haystack, $marker), 2);

		return $load_string;
	}

	public function find_opengl_version($opengl_load_string = '')
	{
		if (!empty($opengl_load_string)) {
			$haystack = $opengl_load_string;
			$version = $this->find_string($haystack, ', version ', ' ');
		} elseif (isset($this->startup_string)) {
			$haystack = $this->startup_string;
			$version = $this->find_string($haystack, ': OpenGL loaded successfully, version ', ' ');
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
			$version = $this->find_string($haystack, ': OpenGL loaded successfully, version ', ' ');
		}

		if ($version === false) {
			return false;
		}

		return $version;
	}

	public function find_opengl_vendor_info($opengl_load_string = '', $marker = '')
	{
		if (!empty($opengl_load_string)) {
			$haystack = $opengl_load_string;
		} elseif (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$vendor = $this->find_string($haystack, $marker, ', shading language ');
		if ($vendor === false) {
			return false;
		}

		return $vendor;
	}

	public function find_opengl_shader_version($opengl_load_string = '')
	{
		if (!empty($opengl_load_string)) {
			$haystack = $opengl_load_string;
		} elseif (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$shader = $this->find_string($haystack, ', shading language ');
		if ($shader === false) {
			return false;
		}

		return $shader;
	}

	// $altmarkers array() of strings
	public function find_module_load_string($marker, $altmarkers = null, $end_marker = null, $haystack = null)
	{
		// ': [AMF Encoder] Version ' - loaded, 1.4.3.11
		// ': [AMF] Version ' - loaded, 1.9.9.11
		// ': [AMF] Version ' - loaded, 2.x
		if (isset($haystack)) {
			// avoid setting $haystack to something else
		} elseif (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$module_load_string = '';
		$module_load_string_marker_pos = strpos($haystack, $marker);
		if ($module_load_string_marker_pos === false) {
			if (isset($altmarkers) && is_array($altmarkers)) {
				foreach ($altmarkers as $altmarker) {
					$module_load_string_marker_pos = strpos($haystack, $altmarker);
					if ($module_load_string_marker_pos !== false) {
						$marker = $altmarker;
						break;
					}
				}
			}
		}
		if ($module_load_string_marker_pos !== false) {
			$haystack_len = strlen($haystack);
			$offset = -1 * ($haystack_len - $module_load_string_marker_pos);
			$module_load_string_start_pos = strrpos($haystack, "\n", $offset);
			$module_load_string_end_pos = strpos($haystack, "\n", $module_load_string_marker_pos);
			$module_load_string = ltrim(substr($haystack, $module_load_string_start_pos,
					$module_load_string_end_pos - $module_load_string_start_pos));
			//*/
			//$module_load_string = $this->find_string($haystack, $marker, $end_marker);
		}

		return $module_load_string;
	}

	public function find_amf_encoder_module_load_string()
	{
		// ': [AMF Encoder] Version ' - loaded, 1.4.3.11
		// ': [AMF] Version ' - loaded, 1.9.9.11
		// ': [AMF] Version ' - loaded, 2.x
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$amf_encoder_string = $this->find_module_load_string(': [AMF] Version ', array(': [AMF Encoder] Version '));

		return $amf_encoder_string;
	}

	public function find_amf_encoder_version()
	{
		// ': [AMF Encoder] Version ' - loaded, 1.4.3.11
		// ': [AMF] Version ' - loaded, 1.9.9.11+ / 2.x+
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$amf_encoder_version = $this->find_string($haystack, ': [AMF] Version ');
		if ($amf_encoder_version === false) {
			$amf_encoder_version = $this->find_string($haystack, ': [AMF Encoder] Version ');
		}
		// might not need to do this here
		if ($amf_encoder_version !== false) {
			$this->has_amf_support = true;
		}

		return $amf_encoder_version;
	}

	public function find_browser_source_version($haystack = null)
	{
		// ": [browser_source: 'Version: "
		if (isset($haystack)) {
			// just need to avoid the other branches if this is set
		} elseif (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$browser_source_version = $this->find_string($haystack, ": [browser_source: 'Version: ", "']");
		// probably shouldn't do this
		if ($browser_source_version !== false) {
			$this->has_browser_source = true;
		}

		return $browser_source_version;
	}

	public function find_browser_source_module_load_string()
	{
		// ": [browser_source: 'Version: "
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		//$browser_source_string = $this->find_string($haystack, ": [browser_source: 'Version: ", "']");
		$browser_source_string = $this->find_module_load_string(": [browser_source: 'Version: ", null, "']", $haystack);

		return $browser_source_string;
	}

	/**
	 * @return array
	 */
	public function find_loaded_modules($haystack = null)
	{
		if (isset($haystack)) {
			// just use $haystack
		} elseif (isset($this->loaded_modules_string)) {
			$haystack = $this->loaded_modules_string;
		} else {
			return false;
		}

		$loaded_modules = array();

		$stream = fopen('php://temp','rb+');
		fwrite($stream, $haystack);
		rewind($stream);
		while (!feof($stream)) {
			$line = fgetss($stream);
			$obs_log_line = new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLogLine($line);
			$obs_log_line->load_data();
			if ($obs_log_line->is_valid()) {
				$loaded_modules[] = trim($obs_log_line->get_item_string());
			}
			unset($obs_log_line);
		}
		fclose($stream);

		return $loaded_modules;
	}

	public function find_loaded_scene_collection($offset = 0, $haystack = null)
	{
		/*
		19:44:05.573: Switched to scene 'Scene'
		19:44:05.573: ------------------------------------------------
		19:44:05.573: Loaded scenes:
		19:44:05.573: - scene 'Scene':
		19:44:05.573:     - source: 'Display Capture' (monitor_capture)
		19:44:05.573: ------------------------------------------------
		19:44:05.649:
		*/
		if (!isset($haystack) && isset($this->raw_string)) {
			$haystack = $this->raw_string;
		} else {
			return false;
		}
		$scene_collection_marker = ': Loaded scenes:';
		$scene_collection_end_marker_1 = ': Switched to scene collection';
		$scene_collection_end_marker_2 = ': ------------------------------------------------';
		$scene_collection_marker_pos = strpos($haystack, $scene_collection_marker, $offset);
		$scene_collection_end_marker_1_pos = strpos($haystack, $scene_collection_end_marker_1, $scene_collection_marker_pos);
		$scene_collection_end_marker_2_pos = strpos($haystack, $scene_collection_end_marker_2, $scene_collection_end_marker_1_pos);
		/*$opts = array('end_marker' => 
			array('offset' => $scene_collection_end_marker_2_pos,
				'include' => true));*/
		$opts = array('offset' => $scene_collection_marker_pos);
		$scene_collection = $this->find_string($haystack, $scene_collection_marker,
			$scene_collection_end_marker_2, $opts);
	}

	// probably rewrite as a process function since we can get names by processing loaded scenes
	public function find_game_capture_source_names($string = null)
	{
		// ': [game-capture: 'source-name'] '
		if (isset($string)) {
		} elseif (isset($this->raw_string)) {
			$string = $this->raw_string;
		} else {
			return false;
		}

		$game_capture = 0;
		$game_capture_marker = ': [game-capture: ';
		$game_capture_sources = array();
		do {
			$game_capture = strpos($string, ': [game-capture: ', $game_capture);
			if ($game_capture !== false) {
				$opts = array('offset' => $game_capture);
				$source_name = $this->find_string($string, $game_capture_marker, "'] ", $opts);
				if ($source_name !== false) {
					$game_capture_sources[] = $source_name;
				}
			}
		} while ($game_capture !== false);

		return $game_capture_sources;
	}

	public function find_encoders()
	{
		// ': [NVENC encoder: '
		// ": [NVENC encoder: 'recording_h264'] settings:"
		// ': [CoreAudio AAC: '
		// ": [ffmpeg muxer: 'adv_file_output']"
	}

	public function find_nvenc_encoder($string = null)
	{
		/*
		08:55:31.020: [NVENC encoder: 'recording_h264'] settings:
		08:55:31.020: 	rate_control: CQP
		08:55:31.020: 	bitrate:      0
		08:55:31.020: 	cqp:          18
		08:55:31.020: 	keyint:       250
		08:55:31.020: 	preset:       hq
		08:55:31.020: 	profile:      high
		08:55:31.020: 	level:        auto
		08:55:31.020: 	width:        1920
		08:55:31.020: 	height:       1080
		08:55:31.020: 	2-pass:       true
		08:55:31.020: 	b-frames:     2
		08:55:31.020: 	GPU:          0
		08:55:31.020: 
		08:55:31.621: [CoreAudio AAC: 'Track1']: settings:
		08:55:31.621: 	mode:          AAC
		08:55:31.621: 	bitrate:       160
		08:55:31.621: 	sample rate:   44100
		08:55:31.621: 	cbr:           on
		08:55:31.621: 	output buffer: 1536
		*/
		if (isset($string)) {
		} elseif (isset($this->raw_string)) {
			$string = $this->raw_string;
		} else {
			return false;
		}

		
	}

	public function find_nvenc_encoders($string = null)
	{
		if (isset($string)) {
		} elseif (isset($this->raw_string)) {
			$string = $this->raw_string;
		} else {
			return false;
		}
		$nvenc_encoder_marker = ': [NVENC encoder: ';
		$nvenc_encoder_pos = strpos($string, $nvenc_encoder_marker);
		
	}

	public function process_sources()
	{
		
	}

	public function has_coreaudio()
	{
		// ': [CoreAudio encoder]: CoreAudio' - didn't load
		// ': [CoreAudio encoder]: Adding' - loaded
		if (isset($this->has_coreaudio)) {
			return $this->has_coreaudio;
		}

		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$coreaudio_added = strpos($haystack, ': [CoreAudio encoder]: Adding');
		if ($coreaudio_added !== false) {
			return true;
		} else {
			return false;
		}
	}

	public function has_amf_support()
	{
		// @todo: consider moving this logic to the AMF_Encoder class
		// ': [AMF Encoder] Version' - loaded, 1.4.3.11
		// ': [AMF] Version ' - loaded, 1.9.9.11
		if (isset($this->has_amf_support)) {
			return $this->has_amf_support;
		}

		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$has_amf_support = strpos($haystack, ': [AMF] Version ');
		if ($has_amf_support !== false) {
			return true;
		} else {
			$has_amf_support = strpos($haystack, ': [AMF Encoder] Version ');
			if ($has_amf_support !== false) {
				return true;
			}
			return false;
		}
	}

	public function has_nvenc_support()
	{
		// ': NVENC supported'
		if (isset($this->has_nvenc_support)) {
			return $this->has_nvenc_support;
		}

		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$has_nvenc_support = strpos($haystack, ': NVENC supported');
		if ($has_nvenc_support !== false) {
			return true;
		} else {
			return false;
		}
	}

	public function has_browser_source()
	{
		// ": [browser_source: 'Version: "
		if (isset($this->has_browser_source)) {
			return $this->has_browser_source;
		}

		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$has_browser_source = strpos($haystack, ": [browser_source: 'Version: ");
		if ($has_browser_source !== false) {
			return true;
		} else {
			return false;
		}
	}

	public function has_vlc()
	{
		// ': VLC found, VLC video source enabled'
		if (isset($this->has_vlc)) {
			return $this->has_vlc;
		}

		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$has_vlc = strpos($haystack, ': VLC found, VLC video source enabled');
		if ($has_vlc !== false) {
			return true;
		} else {
			return false;
		}
	}

	public function has_blackmagic_support()
	{
		// ': No blackmagic support' // only a negative string is output
		if (isset($this->has_blackmagic_support)) {
			return $this->has_blackmagic_support;
		}

		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$has_blackmagic_support = strpos($haystack, ': No blackmagic support');
		if ($has_blackmagic_support === false) {
			return true;
		} else {
			return false;
		}
	}

	public function find_startup_string()
	{
		// all init is above this line:
		$startup_marker = ': ==== Startup complete ===============================================';
		$startup_end_pos = strpos($this->raw_string, $startup_marker) + strlen($startup_marker);
		return substr($this->raw_string, 0, $startup_end_pos);
	}

	public function find_loaded_modules_string()
	{
		if (isset($this->startup_string)) {
			$haystack = $this->startup_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		}

		$loaded_modules = $this->find_string($haystack, ':   Loaded Modules:',
			': ==== Startup complete ===============================================');

		// check for https://github.com/jp9000/obs-studio/commit/4fd66d4d1e2ffdea99d51a1a78321441dbacbdd0
		$tmp = $this->find_string($loaded_modules, '', '---------------------------------');
		if ($tmp !== false) {
			$loaded_modules = $tmp;
		}

		return $loaded_modules;
	}

	public function find_profiler_string_pos()
	{
		if (isset($this->profiler_pos)) {
			return $this->profiler_pos;
		}
		if (isset($this->raw_string)) {
			$profiler_pos = strpos($this->raw_string, ': == Profiler Results =============================');

			return $profiler_pos;
		}
		return false;
	}

	public function find_profiler_string()
	{
		if ($this->has_profiler() !== false) {
			if (isset($this->profiler_string)) {
				return $this->profiler_string;
			}

			$profiler_pos = $this->find_profiler_string_pos();
			$profilerString = false;
			if ($profiler_pos !== false) {
				$profilerString = substr($this->raw_string, $profiler_pos);
			}

			return $profilerString;
		}
	}

	public function find_memory_leaks()
	{
		if (isset($this->profiler_string)) {
			$haystack = $this->profiler_string;
		} elseif (isset($this->raw_string)) {
			$haystack = $this->raw_string;
		} else {
			return false;
		}

		$mem_leaks = false;
		$mem_leak_label = 'Number of memory leaks: ';
		$mem_leak_pos = strpos($haystack, $mem_leak_label);
		if ($mem_leak_pos !== false) {
			$mem_leaks = trim(substr($haystack, $mem_leak_pos + strlen($mem_leak_label)));
		}
		return $mem_leaks;
	}

	public function count_sessions()
	{
		$this->recording_sessions = $this->count_recording_sessions();
		$this->streaming_sessions = $this->count_streaming_sessions();
	}

	public function count_recording_sessions()
	{
		$this->recording_session_starts = $this->count_recording_starts();
		$this->recording_session_stops = $this->count_recording_stops();
		if ($this->recording_session_starts !== $this->recording_session_stops) {
			$this->has_incomplete_recording_session = true;
		} else {
			$this->has_incomplete_recording_session = false;
			//$sessions = $this->recording_session_stops;
		}

		return $this->recording_session_stops;
	}

	public function count_recording_starts($raw_string = null)
	{
		$recording_start_marker = ': ==== Recording Start ===============================================';
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}
		return substr_count($string, $recording_start_marker);
	}

	public function count_recording_stops($raw_string = null)
	{
		$recording_stop_marker = ': ==== Recording Stop ================================================';
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}
		return substr_count($string, $recording_stop_marker);
	}

	public function has_incomplete_recording_session()
	{
		if (!isset($this->has_incomplete_recording_session)) {
			$this->count_recording_sessions();
		}
		return $this->has_incomplete_recording_session;
	}

	// ': ==== Streaming Start ==============================================='
	// ': ==== Streaming Stop ================================================'
	public function count_streaming_sessions()
	{
		$this->streaming_session_starts = $this->count_streaming_starts();
		$this->streaming_session_stops = $this->count_streaming_stops();
		if ($this->streaming_session_starts !== $this->streaming_session_stops) {
			$this->has_incomplete_streaming_session = true;
		} else {
			$this->has_incomplete_streaming_session = false;
		}

		return $this->streaming_session_stops;
	}

	public function count_streaming_starts($raw_string = null)
	{
		$streaming_start_marker = ': ==== Streaming Start ===============================================';
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}
		return substr_count($string, $streaming_start_marker);
	}

	public function count_streaming_stops($raw_string = null)
	{
		$streaming_stop_marker = ': ==== Streaming Stop ================================================';
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}
		return substr_count($string, $streaming_stop_marker);
	}

	public function has_incomplete_streaming_session()
	{
		if (!isset($this->has_incomplete_streaming_session)) {
			$this->count_streaming_sessions();
		}
		return $this->has_incomplete_streaming_session;
	}

	public function find_skipped_frames($haystack = null)
	{
		
	}

	public function has_max_audio_buffering($string = null)
	{
		if (isset($this->has_max_audio_buffering)) {
			return $this->has_max_audio_buffering;
		}

		if (!isset($string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($string)) {
			$string = $this->raw_string;
		}

		$max_buff = strpos($string, ': Max audio buffering reached!');
		if ($max_buff === false) {
			return false;
		} else {
			return true;
		}
	}

	public function add_audio_settings($audio_settings)
	{
		$this->audio_settings[] = $audio_settings;
		$this->audio_setting_resets++;
	}

	public function add_video_settings($video_settings)
	{
		$this->video_settings[] = $video_settings;
		$this->video_setting_resets++;
	}

	public function find_game_capture($string)
	{
		// ': [game-capture: '
		// '] attempting to hook process: '
		// '] Hooked to process: '
		// '] Hooked D3D9'
		// '] Hooked DXGI'
		// '] d3d11 shared texture capture successful'
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}
	}

	// '] hook not loaded yet, retrying..'
	// '] d3d9 memory capture successful'
	// '] memory capture successful'
	public function has_game_capture_fall_back_to_memcap($raw_string = null)
	{
		// look for gamecap fallback
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}

		$hook_retry = strpos($string, '] hook not loaded yet, retrying..');
		if ($hook_retry !== false) {
			// has hook retry at $hook_retry
			$d3d9_memcap = strpos($string, '] d3d9 memory capture successful', $hook_retry);
			if ($d3d9_memcap !== false)
			{
				// has d3d9_memcap at $d3d9_memcap
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function has_d3d11_error_88760868($raw_string = null)
	{
		// '] d3d9_shmem_capture: GetRenderTargetData failed (0x88760868)'
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}

		$e88760868 = strpos($string, '] d3d9_shmem_capture: GetRenderTargetData failed (0x88760868)');
		if ($e88760868 === false) {
			$e88760868 = strpos($string, ': GetRenderTargetData failed (0x88760868)');
			if ($e88760868 === false) {
				return false;
			}
		} else {
			return true;
		}
	}

	public function has_d3d11_error_887A0005($raw_string = null)
	{
		// ': device_texture_create (D3D11): Failed to create 2D texture (887A0005)'
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}

		$e887A0005 = strpos($string, ': device_texture_create (D3D11): Failed to create 2D texture (887A0005)');
		if ($e887A0005 === false) {
			return false;
		} else {
			return true;
		}
	}

	/* has_weak_cpu
	 *
	 * Determine if a user's system has a weak CPU.
	 *
	 * Need some guidance on what qualifies as "weak".
	 * Celerons?  Intel U class?  Intel Y class?  Intel T class?
	 * https://www.intel.com/content/www/us/en/processors/processor-numbers.html
	 * How about on AMD's side?
	 */
	public function has_weak_cpu($string = null)
	{
		// Celeron, "CPU N####"
		if (empty($string) && isset($this->cpu_info)) {
			$string = $this->get_cpu_name();
		} else {
			return false;
		}

		if (strpos($string, 'Celeron') !== false
			|| strpos($string, 'CPU N') !== false) {
			return true;
		}

		return false;
	}

	/* has_weak_gpu
	 *
	 * Determine if a user is using a weak GPU.
	 *
	 * Need some guidance on what qualifies as "weak".
	 * Would an NVIDIA GeForce GT 1030 be weak?
	 */
	public function has_weak_gpu($string = null)
	{
		// NVIDIA GeForce GT 740
		if (empty($string) && isset($this->video_adapters)) {
			$adapter_count = $this->get_video_adapter_count();
		} else {
			return false;
		}

		if ($adapter_count > 1) {
			$string = $this->get_renderer_adapter();
		} elseif ($adapter_count = 1) {
			$string = $this->get_video_adapter_name();
		} else {
			return false;
		}

		if (strpos($string, 'GeForce GT ') !== false) {
			return true;
		}

		return false;
	}

	// An OBS Studio log should NOT show this.
	public function has_clr_host_plugin($raw_string = null)
	{
		// ": LoadLibrary failed for '../../obs-plugins/64bit/CLRHostPlugin.dll'"
		// ": Module '../../obs-plugins/64bit/CLRHostPlugin.dll' not found"
		if (!isset($raw_string) && !isset($this->raw_string)) {
			return false;
		} elseif (!isset($raw_string)) {
			$string = $this->raw_string;
		} else {
			$string = $raw_string;
		}

		$clr_host_plugin = strpos($string, ": LoadLibrary failed for '../../obs-plugins/64bit/CLRHostPlugin.dll'");
		if ($clr_host_plugin === false) {
			$clr_host_plugin = strpos($string, ": Module '../../obs-plugins/64bit/CLRHostPlugin.dll' not found");
		}
		if ($clr_host_plugin === false) {
			return false;
		} else {
			return true;
		}
	}

	public function set_file_path($file_path)
	{
		if (file_exists($file_path)) {
			$this->file_path = $file_path;
		}
	}

	public function is_os_windows()
	{
		if (isset($this->os_info['name']) && $this->os_info['name'] === 'Windows') {
			return true;
		} else {
			return false;
		}
	}

	public function is_os_windows_7()
	{
		if ($this->is_os_windows() && isset($this->os_info['version'])
			&& $this->os_info['version']['version'] === '6.1') {
			return true;
		} else {
			return false;
		}
	}

	public function is_os_windows_8()
	{
		if ($this->is_os_windows() && isset($this->os_info['version'])
			&& $this->os_info['version']['version'] === '6.2') {
			return true;
		} else {
			return false;
		}
	}

	public function is_os_windows_81()
	{
		if ($this->is_os_windows() && isset($this->os_info['version'])
			&& $this->os_info['version']['version'] === '6.3') {
			return true;
		} else {
			return false;
		}
	}

	public function is_os_windows_10()
	{
		if ($this->is_os_windows() && isset($this->os_info['version'])
			&& $this->os_info['version']['version'] === '10.0') {
			return true;
		} else {
			return false;
		}
	}

	public function is_os_mac()
	{
		if (isset($this->os_info['name']) && $this->os_info['name'] === 'Mac OS X') {
			return true;
		} else {
			return false;
		}
	}

	public function is_os_linux()
	{
		if (isset($this->os_info['name']) && $this->os_info['name'] === 'Linux') {
			return true;
		} else {
			return false;
		}
	}

	public function is_run_as_admin()
	{
		if (isset($this->run_as_admin)) {
			return $this->run_as_admin;
		} else {
			return null;
		}
	}

	public function is_renderer_d3d11()
	{
		if (isset($this->renderer) && $this->renderer === 'D3D11') {
			return true;
		} else {
			return false;
		}
	}

	public function is_renderer_opengl()
	{
		if (isset($this->renderer) && $this->renderer === 'OpenGL') {
			return true;
		} else {
			return false;
		}
	}

	public function is_renderer_on_igpu()
	{
		// 'Intel(R) HD Graphics 5500'
		// 'AMD Radeon(TM) R5 Graphics'
		if ($this->is_renderer_d3d11()) {
			$active_adapter = $this->get_renderer_adapter();
			if (strpos($active_adapter, 'Intel(R)') !== false || 
				(strpos($active_adapter, 'AMD Radeon(TM) R') !== false
				&& strpos($active_adapter, ' Graphics') !== false)) {
				return true;
			}
		}
		return false;
	}

	public function is_renderer_on_igpu_not_discrete()
	{
		if (count($this->video_adapters) > 1
			&& $this->is_renderer_on_igpu()) {
			return true;
		}
		return false;
	}

	public function is_obs_version_custom($version = null)
	{
		if (!isset($version) && isset($this->obs_version_info['version'])) {
			$version = $this->obs_version_info['version'];
		} elseif (!isset($this->obs_version_info['version'])) {
			return null; // maybe?
		}

		$is_custom = strpos($version, '-');
		if ($is_custom !== false) {
			return true;
		}
		return false;
	}

	public function is_obs_from_git($version = null)
	{
		if (!isset($version) && isset($this->obs_version_info['version'])) {
			$version = $this->obs_version_info['version'];
		} elseif (!isset($this->obs_version_info['version'])) {
			return null; // maybe?
		}

		$is_git = strpos($version, '-g');
		if ($is_git !== false) {
			return true;
		}
		return false;
	}

	public function is_obs_ftl_enabled($version = null)
	{
		if (!isset($version) && isset($this->obs_version_info['version'])) {
			$version = $this->obs_version_info['version'];
		} elseif (!isset($this->obs_version_info['version'])) {
			return null; // maybe?
		}

		$is_ftl = strpos($version, '-ftl');
		if ($is_ftl !== false) {
			return true;
		}
		return false;
	}

	public function get_cpu_name()
	{
		if (isset($this->cpu_info, $this->cpu_info['name'])) {
			return $this->cpu_info['name'];
		}
	}

	public function get_cpu_speed()
	{
		if (isset($this->cpu_info, $this->cpu_info['speed'])) {
			return $this->cpu_info['speed'];
		}
	}

	public function get_cpu_physical_cores()
	{
		if (isset($this->cpu_info, $this->cpu_info['cores'],
			$this->cpu_info['cores']['physical'])) {
			return $this->cpu_info['cores']['physical'];
		}
	}

	public function get_cpu_logical_cores()
	{
		if (isset($this->cpu_info, $this->cpu_info['cores'],
			$this->cpu_info['cores']['logical'])) {
			return $this->cpu_info['cores']['logical'];
		}
	}

	public function get_memory_physical_total()
	{
		if (isset($this->mem_info, $this->mem_info['physical'],
			$this->mem_info['physical']['total'])) {
			return $this->mem_info['physical']['total'];
		}
	}

	public function get_memory_physical_free()
	{
		if (isset($this->mem_info, $this->mem_info['physical'],
			$this->mem_info['physical']['free'])) {
			return $this->mem_info['physical']['free'];
		}
	}

	// @todo: implement
	public function get_os_info()
	{
		
	}

	// @todo: implement
	public function get_os_version_info()
	{
		
	}

	public function get_os_name()
	{
		if (isset($this->os_info, $this->os_info['name'])) {
			return $this->os_info['name'];
		}
	}

	public function get_os_version()
	{
		if (isset($this->os_info, $this->os_info['version'],
			$this->os_info['version']['version'])) {
			return $this->os_info['version']['version'];
		}
	}

	// get pretty OS version string
	public function get_os_version_string()
	{
		$os_name = $this->get_os_name();
		$version_string = "n/a";
		if ($os_name === 'Windows') {
			$version_string = $this->get_os_version_string_win();
		} elseif ($os_name === 'Mac OS X') {
			$version_string = $this->get_os_version_string_mac();
		} elseif ($os_name === 'Linux') {
			$version_string = $this->get_os_version_string_nix();
		}

		return $version_string;
	}

	public function get_os_version_string_win()
	{
		$string = "n/a";
		if (isset($this->os_info, $this->os_info['version'],
			$this->os_info['version']['version'], $this->os_info['version']['build'],
			$this->os_info['version']['revision'], $this->os_info['version']['bitness'])) {
			// Version: 10.0 Build 14393 (revision: 2125; 64-bit)
			$os = $this->os_info['version'];
			$string = "Version: ${os['version']} Build ${os['build']} (revision: ${os['revision']}; ${os['bitness']}-bit)";
		}

		return $string;
	}

	public function get_os_version_string_mac($include_kernel = false)
	{
		$string = "n/a";
		if (isset($this->os_info, $this->os_info['version'],
			$this->os_info['version']['version'], $this->os_info['version']['build'],
			$this->os_info['version']['kernel'])) {
			// Version 10.13.3 (Build 17D102)
			$os = $this->os_info['version'];
			$string = "Version: ${os['version']} (Build ${os['build']})";

			if ($include_kernel === true) {
				$string .= "\nKernel Version: ${os['kernel']}";
			}
		}

		return $string;
	}

	public function get_os_version_string_nix()
	{
		$string = "n/a";
		if (isset($this->os_info, $this->os_info['version'],
			$this->os_info['version']['kernel'], $this->os_info['version']['distribution'])) {
			// Kernel Version: Linux 4.15.3-1-ARCH
			// Distribution: "Arch Linux" Unknown
			$os = $this->os_info['version'];
			$string = "Kernel Version: ${os['kernel']}\nDistribution: ${os['distribution']}";
		}

		return $string;
	}

	public function get_os_build()
	{
		if (isset($this->os_info, $this->os_info['version'],
			$this->os_info['version']['build'])) {
			return $this->os_info['version']['build'];
		}
	}

	public function get_os_revision()
	{
		if (isset($this->os_info, $this->os_info['version'],
			$this->os_info['version']['revision'])) {
			return $this->os_info['version']['revision'];
		}
	}

	public function get_os_bitness()
	{
		if (isset($this->os_info, $this->os_info['version'],
			$this->os_info['version']['bitness'])) {
			return $this->os_info['version']['bitness'];
		}
	}

	public function get_run_as_admin()
	{
		if (isset($this->run_as_admin)) {
			return $this->run_as_admin;
		}
	}

	public function get_aero_status()
	{
		if (isset($this->aero_status)) {
			return $this->aero_status;
		}
	}

	public function get_portable_mode_status()
	{
		if (isset($this->portable_mode)) {
			return $this->portable_mode;
		}
	}

	public function get_obs_version()
	{
		if (isset($this->obs_version_info['version'])) {
			return $this->obs_version_info['version'];
		}
	}

	public function get_obs_version_bitness()
	{
		if (isset($this->obs_version_info['bitness'])) {
			return $this->obs_version_info['bitness'];
		}
	}

	public function get_audio_samples_per_sec()
	{
		if (isset($this->audio_settings[0], $this->audio_settings[0]->samples_per_sec)) {
			return $this->audio_settings[0]->samples_per_sec;
		}
	}

	public function get_audio_speakers()
	{
		if (isset($this->audio_settings[0], $this->audio_settings[0]->speakers)) {
			return $this->audio_settings[0]->speakers;
		}
	}

	public function get_renderer_name()
	{
		if (isset($this->renderer)) {
			return $this->renderer;
		}
	}

	public function get_renderer_adapter()
	{
		return $this->renderer_info['adapter'];
	}

	public function get_renderer_feature_level()
	{
		return $this->renderer_info['feature_level'];
	}

	public function get_video_adapter($adapter_num = 1)
	{
		$adapter_num--;
		if (isset($this->video_adapters[$adapter_num])) {
			return $this->video_adapters[$adapter_num];
		}
	}

	public function get_video_adapter_number($adapter_num = 1)
	{
		$adapter_num--;
		if (isset($this->video_adapters[$adapter_num],
			$this->video_adapters[$adapter_num]->number)) {
			return $this->video_adapters[$adapter_num]->number;
		}
	}

	public function get_video_adapter_name($adapter_num = 1)
	{
		$adapter_num--;
		if (isset($this->video_adapters[$adapter_num],
			$this->video_adapters[$adapter_num]->name)) {
			return $this->video_adapters[$adapter_num]->name;
		}
	}

	public function get_video_adapter_dedicated_vram($adapter_num = 1)
	{
		$adapter_num--;
		if (isset($this->video_adapters[$adapter_num],
			$this->video_adapters[$adapter_num]->dedicated_vram)) {
			return $this->video_adapters[$adapter_num]->dedicated_vram;
		}
	}

	public function get_video_adapter_shared_vram($adapter_num = 1)
	{
		$adapter_num--;
		if (isset($this->video_adapters[$adapter_num],
			$this->video_adapters[$adapter_num]->shared_vram)) {
			return $this->video_adapters[$adapter_num]->shared_vram;
		}
	}

	public function get_video_adapter_outputs($adapter_num = 1)
	{
		$adapter_num--;
		if (isset($this->video_adapters[$adapter_num],
			$this->video_adapters[$adapter_num]->outputs)) {
			return $this->video_adapters[$adapter_num]->outputs;
		}
	}

	public function get_video_adapter_count()
	{
		if (isset($this->video_adapters)) {
			return count($this->video_adapters);
		} else {
			return 0;
		}
	}

	/* return JSON summary/results
	 */
	public function build_json_result($data = null)
	{
		$now = time();
		$results = ['unixtime' => $now,
			'datetime' => date('c', $now),
			'analyzer_results' => null];

		if (!isset($data)) {
			$data = $this->issues;
		}
		$results['analyzer_results'] = $data;

		$json = json_encode($results, JSON_PRETTY_PRINT);

		return $json;
	}


	/* flag_issues
	 * 
	 */
	public function flag_issues()
	{
		/* rules need to access constants, which seem odd associated with OBSStudioLog
		 * (LATEST_OBS_VERSION, LATEST_AMF_ENCODER_VERSION, LATEST_WINDOWS_7_VERSION, LATEST_BROWSER_SOURCE_VERSION, etc.)
		 * might make more sense to write an Analyzer class above OBSStudioLog that tracks things like that,
		 * or move those constants to Config or somewhere else
		 */
		$issue_handler = new \RytoEX\OBS\LogAnalyzer\Issue\Handler($this);
		//$issue_handler->obs_log_object = $this;
		$issue_handler->run_rules();
		$this->issues = array_merge($this->issues, $issue_handler->issues);
	}
}
