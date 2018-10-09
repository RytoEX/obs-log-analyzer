<?php

namespace RytoEX\OBS\LogAnalyzer\Config;

class Windows10Info
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;
	use \RytoEX\OBS\LogAnalyzer\Utils\JSONFileIOTrait;

	private $win10_release_html;
	private $config;

	//https://technet.microsoft.com/en-us/windows/release-info
	//https://winrelinfo.blob.core.windows.net/winrelinfo/en-US.html
	//https://support.microsoft.com/en-us/help/4049370
	public $data_url = 'https://winrelinfo.blob.core.windows.net/winrelinfo/en-US.html';
	public $data_dir;
	public $cache_dir;
	public $os_info_dir;
	public $cache_file;
	public $info_file;
	public $info_file_base;
	public $release_data;
	public $rec_ver;
	public $builds;

	public $update_result;
	public $revision_update_count = 0;
	public $new_builds_count = 0;

	public function __construct($init = true, $fetch_new_html = true, $update_json = true)
	{
		$this->config = new \RytoEX\OBS\LogAnalyzer\Config\Config(false);
		$this->data_dir = __DIR__ . '/data';
		$this->cache_dir = $this->data_dir . '/cache';
		$this->os_info_dir = $this->data_dir . '/os_info';
		$this->cache_file = $this->cache_dir . '/win10_release_info.html';
		$this->info_file = $this->os_info_dir . '/windows_10.json';
		$this->info_file_base = $this->os_info_dir . '/windows_10_base.json';

		if (file_exists($this->info_file_base)) {
			if (!file_exists($this->info_file))
				copy($this->info_file_base, $this->info_file);
		} else {
			// @todo: fetch from GitHub?
		}

		if ($fetch_new_html) {
			$this->win10_release_html = $this->fetch_new_version_html();
		} else {
			$this->win10_release_html = $this->load_cached_version_html();
		}
		if ($init) {
			$this->release_data = $this->find_data();

			if ($update_json && $this->release_data !== false) {
				$update_res = $this->update_json($this->release_data);
			}
		}
	}

	// find all data from HTML source
	public function find_data()
	{
		if (!isset($this->win10_release_html) || empty($this->win10_release_html))
			return false;

		$this->rec_ver = $this->find_recommended_version();
		$this->builds = $this->find_builds();

		if ($this->builds === false)
			return false;

		$release_data = $this->builds;
		foreach ($this->builds as $idx => $build_info) {
			$release_data[$idx] += $this->find_latest_version_data_by_version_name($build_info['version_name']);
		}

		return $release_data;
	}

	public function cache_version_html($html_string = '')
	{
		if (isset($html_string) && !empty($html_string)) {
			$html = $html_string;
		} elseif (isset($this->win10_release_html) && !empty($this->win10_release_html)) {
			$html = $this->win10_release_html;
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

	public function find_builds()
	{
		if (!isset($this->win10_release_html) || empty($this->win10_release_html))
			return false;

		$string = $this->win10_release_html;
		$builds = array();
		$version_info = array();

		$pattern = '/<\/span> Version (?<version_name>(?<version_num>\d+).*) \(OS build (?<build>\d+)\)/';
		$res = preg_match_all($pattern, $string, $version_info);
		if ($res === false || $res === 0)
			return false;

		foreach ($version_info['build'] as $idx => $build_num) {
			$builds[$idx]['build_num'] = $version_info['build'][$idx];
			$builds[$idx]['version_name'] = $version_info['version_name'][$idx];
			$builds[$idx]['version_num'] = $version_info['version_num'][$idx];
		}

		return $builds;
	}

	// old approach
	public function find_version_data_by_service_option()
	{
		if (!isset($this->win10_release_html) || empty($this->win10_release_html))
			return false;

		$string = $this->win10_release_html;

		$string = $this->find_string($string, '<div id="winrelinfo_container">', '</div>');
		$offset = strpos($string, '<tr') + 3;
		$offset = strpos($string, '<tr', $offset) + 3;

		$opts = array('offset' => $offset,
			'return' => array('string', 'value_start_pos'));
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['servicing_option'] = $return['string'];
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['version'] = $return['string'];
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['build_string'] = $return['string'];
		$build_string_components = explode('.', $win10['build_string']);
		$win10['build'] = $build_string_components[0];
		$win10['revision'] = $build_string_components[1];
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['availability_date'] = $return['string'];
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['revision_date'] = $return['string'];

		return $win10;
	}

	public function find_latest_version_data_by_version_name($version_name)
	{
		if (!isset($this->win10_release_html) || empty($this->win10_release_html))
			return false;

		if (!isset($version_name))
			return false;

		$string = $this->win10_release_html;

		// setup initial substring and offset
		$string = $this->find_string($string, '<div id="winrelinfo_container">', '</div>');

		$pattern = "</span> Version $version_name (OS build ";
		$offset = strpos($string, $pattern);
		$opts = array('offset' => $offset,
			'return' => array('string', 'value_start_pos'));
		$return = $this->find_string($string, '<table ', '</table>', $opts);
		$offset = strpos($return['string'], '<tr>') + 4;
		$opts['offset'] = $offset;
		$return = $this->find_string($return['string'], '<tr>', '</tr>', $opts);
		$string = $return['string'];
		$opts = array('return' => array('string', 'value_start_pos'));

		// get build string and components
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['build_string'] = $return['string'];
		$build_string_components = explode('.', $win10['build_string']);
		$win10['build'] = $build_string_components[0];
		$win10['revision'] = $build_string_components[1];

		// get availability date
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['availability_date'] = $return['string'];

		// get servicing option(s)
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['servicing_options_string'] = $return['string'];
		$svc_opt_components = explode('&bull;', $win10['servicing_options_string']);
		foreach ($svc_opt_components as $key => $svc_opt) {
			$win10['servicing_options'][] = trim(strip_tags($svc_opt));
		}

		// get KB article info
		$opts['offset'] = $return['value_start_pos'];
		$return = $this->find_string($string, '<td>', '</td>', $opts);
		$win10['kb_article']['link_html'] = $return['string'];
		$return = $this->find_string($string, 'href="', '"', $opts);
		$win10['kb_article']['url'] = $return['string'];
		$return = $this->find_string($string, '>KB ', '</a>', $opts);
		$win10['kb_article']['kb_num'] = $return['string'];

		return $win10;
	}

	public function find_recommended_version()
	{
		if (!isset($this->win10_release_html) || empty($this->win10_release_html))
			return false;

		$string = $this->win10_release_html;

		$offset = strpos($string, 'Microsoft recommends');
		$substring = substr($string, 0, $offset);
		$offset = strrpos($substring, '<tr');
		$substring = substr($substring, $offset);
		$offset = strpos($substring, '<td') + 3;
		$offset = strpos($substring, '<td', $offset);
		$opts = array('offset' => $offset);
		$rec_ver = $this->find_string($substring, '<td>', '</td>', $opts);

		return $rec_ver;
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
