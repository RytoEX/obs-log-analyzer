<?php

namespace RytoEX\OBS\LogAnalyzer\Config;

class MacOSInfo
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;
	use \RytoEX\OBS\LogAnalyzer\Utils\JSONFileIOTrait;

	private $macos_release_html;
	private $config;

	public $data_url = 'https://support.apple.com/en-us/HT201260';
	public $data_dir;
	public $cache_dir;
	public $os_info_dir;
	public $info_file;
	public $info_file_base;
	public $release_data;
	public $rec_ver;
	public $major_releases;
	public $latest_major_release;
	public $latest_release;

	public $update_result;
	public $revision_update_count = 0;
	public $new_builds_count = 0;

	public function __construct($init = true, $fetch_new_html = true, $update_json = true)
	{
		$this->config = new \RytoEX\OBS\LogAnalyzer\Config\Config(false);
		$this->data_dir = __DIR__ . '/data';
		$this->cache_dir = $this->data_dir . '/cache';
		$this->os_info_dir = $this->data_dir . '/os_info';
		$this->cache_file = $this->cache_dir . '/macos_release_info.html';
		$this->info_file = $this->os_info_dir . '/macos.json';
		$this->info_file_base = $this->os_info_dir . '/macos_base.json';

		if (file_exists($this->info_file_base)) {
			if (!file_exists($this->info_file))
				copy($this->info_file_base, $this->info_file);
		} else {
			// @todo: fetch from GitHub?
		}

		if ($fetch_new_html) {
			$this->macos_release_html = $this->fetch_new_version_html();
		} else {
			$this->macos_release_html = $this->load_cached_version_html();
		}
		if ($init) {
			$this->release_data = $this->find_data();
			$this->latest_major_release = $this->find_latest_major_release();
			$this->latest_release = $this->find_latest_release();

			if ($update_json && $this->release_data !== false) {
				$update_res = $this->update_json($this->release_data);
			}
		}
	}

	// find all data from HTML source
	public function find_data()
	{
		if (!isset($this->macos_release_html) || empty($this->macos_release_html))
			return false;

		// @todo: find recommended OSX/macOS version?
		$this->major_releases = $this->find_major_releases();

		if ($this->major_releases === false)
			return false;

		$release_data = $this->major_releases;
		foreach ($this->major_releases as $idx => $release_info) {
			$release_data[$idx]['versions'] = $this->find_all_version_data_by_version_name($release_info['version_full_name']);
		}

		return $release_data;
	}

	public function cache_version_html($html_string = '')
	{
		if (isset($html_string) && !empty($html_string)) {
			$html = $html_string;
		} elseif (isset($this->macos_release_html) && !empty($this->macos_release_html)) {
			$html = $this->macos_release_html;
		}
		$res = file_put_contents($this->cache_file, $html);

		return $res;
	}

	public function fetch_new_version_html($cache = true)
	{
		$ch = curl_init($this->data_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		if ($res === false)
			return false;

		if ($cache === true)
			$this->cache_version_html($res);

		return $res;
	}

	public function load_cached_version_html()
	{
		if (!file_exists($this->cache_file))
			return false;

		$contents = file_get_contents($this->cache_file);
		if ($contents === false)
			return false;

		return $contents;
	}

	// find macOS major releases (e.g., Sierra, High Sierra)
	public function find_major_releases()
	{
		if (!isset($this->macos_release_html) || empty($this->macos_release_html))
			return false;

		$string = $this->macos_release_html;
		$builds = array();
		$version_info = array();

		$pattern = '/<h2>(?<version_full_name>(?<brand>macOS|OS X) (?<version_short_name>.+))<\/h2>/';
		$res = preg_match_all($pattern, $string, $version_info);
		if ($res === false || $res === 0)
			return false;

		foreach ($version_info['brand'] as $idx => $brand) {
			$major_releases[$idx]['brand'] = $version_info['brand'][$idx];
			$major_releases[$idx]['version_short_name'] = $version_info['version_short_name'][$idx];
			$major_releases[$idx]['version_full_name'] = $version_info['version_full_name'][$idx];
		}

		return $major_releases;
	}

	public function find_latest_version_data_by_version_name($version_name)
	{
		if (!isset($this->macos_release_html) || empty($this->macos_release_html))
			return false;

		$string = $this->macos_release_html;

		// setup initial substring and offset
		$opts['special_end_marker'] = 'EOF';
		$string = $this->find_string($string, '<div id="sections">', null, $opts);
		$offset = strpos($string, "<h2>$version_name</h2>");
		$opts = array('offset' => $offset);
		$string = $this->find_string($string, '<table', '</table>', $opts);
		$opts['offset'] = strpos($string, '<tr>') + 4;
		$string = $this->find_string($string, '<tr>', '</tr>', $opts);
		$opts = array('return' => array('string', 'value_start_pos'));

		// get latest version number
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$macos['version'] = $return['string'];

		// get build numbers
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$builds_string = htmlspecialchars_decode($return['string']);
		$builds_string = str_replace(array(' ', 'or'), '', $builds_string);
		$builds = explode(',', $builds_string);
		$macos['builds'] = $builds;

		return $macos;
	}

	public function find_all_version_data_by_version_name($version_name)
	{
		if (!isset($this->macos_release_html) || empty($this->macos_release_html))
			return false;

		$string = $this->macos_release_html;

		// setup initial substring and offset
		$opts['special_end_marker'] = 'EOF';
		$string = $this->find_string($string, '<div id="sections"', null, $opts);
		$offset = strpos($string, "<h2>$version_name</h2>");
		$opts = array('offset' => $offset);
		$string = $this->find_string($string, '<table', '</table>', $opts);
		$offset = strpos($string, '<tr>') + 4;
		$opts = array('offset' => $offset,
			'return' => array('string', 'value_start_pos'));

		$macos = array();
		$i = 0;
		while(true) {
		//while($i < 14) {
			// get version number
			$return = $this->find_string($string, '<td', '/td>', $opts);
			if ($return === false)
				break;
			$opts['offset'] = $return['value_start_pos'];
			$return = $this->find_string($string, '>', '<', $opts);
			$version = $return['string'];

			// get build numbers
			$opts['offset'] = $return['value_start_pos'];
			$return = $this->find_string($string, '<td>', '</td>', $opts);
			$opts['offset'] = $return['value_start_pos'];
			$builds_string = $return['string'];
			$builds_string = str_replace(array(',', 'or', '&nbsp;'), '', $builds_string);
			$builds_string = str_replace('  ', ' ', $builds_string);
			$builds = explode(' ', $builds_string);
			$macos[$version] = $builds;
			$i++;
		}

		return $macos;
	}

	public function find_latest_major_release()
	{
		if (!isset($this->release_data))
			return false;

		$versions = $this->release_data[0]['versions'];
		$res = ksort($versions);
		if ($res === false)
			return false;

		return key($versions);
	}

	public function find_latest_release()
	{
		if (!isset($this->release_data))
			return false;

		$versions = $this->release_data[0]['versions'];
		$res = krsort($versions);
		if ($res === false)
			return false;

		return key($versions);
	}

	public function get_latest_major_release()
	{
		if (!isset($this->latest_major_release))
			return false;

		return $this->latest_major_release;
	}

	public function get_latest_release()
	{
		if (!isset($this->latest_release))
			return false;

		return $this->latest_release;
	}

	public function create_updated_json($version_info, $base_file = null)
	{
		if (!isset($base_file))
			$base_file = $this->info_file;

		$json_decoded = $this->read_json_file($base_file);
		if (!isset($json_decoded))
			return false;

		// re-key version_info array against build numbers
		$new_ver = array();
		foreach ($version_info as $version) {
			$new_ver[$version['build']] = $version;
		}

		// modify decoded JSON data with new version info
		foreach ($json_decoded['releases'] as &$release) {
			if ($new_ver[$release['build']]['revision'] > $release['revision']) {
				$release['revision'] = $new_ver[$release['build']]['revision'];
				$this->revision_update_count++;
			}

			unset($new_ver[$release['build']]);
		}

		// check if there are any completely new versions/builds
		if (count($new_ver) > 0) {
			$this->new_builds_count = count($new_ver);
			ksort($new_ver);

			foreach ($new_ver as $build => $new_version) {
				$new_build = array('version' => '10.0',
					'build' => "$build",
					'revision' => $new_version['revision'],
					'ms_version_name' => $new_version['version_name'],
					'ms_version_num' => $new_version['version_num'],
					'ms_public_name' => '',
					'ms_codename' => '');
				$res = array_unshift($json_decoded['releases'], $new_build);
			}
		}

		return $json_decoded;
	}

	public function update_json($version_info, $file = null)
	{
		if (!isset($version_info))
			return false;

		if (!isset($file))
			$file = $this->info_file;

		$json = $this->create_updated_json($version_info, $file);
		if ($json === false)
			return false;

		$res = $this->write_json_file($file, $json, true, true);
		if (is_int($res) && $res >= 0)
			return false;

		return true;
	}
}
