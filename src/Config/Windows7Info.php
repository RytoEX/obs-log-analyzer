<?php

namespace RytoEX\OBS\LogAnalyzer\Config;

// @todo: finish implementing this class

class Windows7Info
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;
	use \RytoEX\OBS\LogAnalyzer\Utils\JSONFileIOTrait;

	// should probably be private eventually
	public $release_html;
	public $sidenav_json;
	public $sidenav_json_cached;
	public $sidenav_json_obj;
	public $sidenav_json_cached_obj;
	public $csv_string;
	public $config;

	//https://support.microsoft.com/en-us/help/4009469
	//https://support.microsoft.com/app/content/api/content/asset/en-us/4009472?iecbust=1526150506528
	private $kb_base_url = 'https://support.microsoft.com/en-us/help/';
	private $kb_sidenav_url = 'https://support.microsoft.com/app/content/api/content/asset/en-us/4009472';
	private $data_url = '';
	public $csv_file_url = '';

	public $data_dir;
	public $cache_dir;
	public $os_info_dir;
	public $cache_kb_html_file;
	public $cache_sidenav_file;
	public $cache_csv_file;
	public $info_file;
	public $info_file_base;
	public $release_data;

	public $update_result;
	public $revision_update_count = 0;
	public $new_builds_count = 0;

	public function __construct($init = true, $fetch_new_data = true, $update_json = true)
	{
		$this->config = new \RytoEX\OBS\LogAnalyzer\Config\Config(false);
		$this->data_dir = __DIR__ . '/data';
		$this->cache_dir = $this->data_dir . '/cache';
		$this->os_info_dir = $this->data_dir . '/os_info';
		$this->cache_kb_html_file = $this->cache_dir . '/win7_release_info.html';
		$this->cache_sidenav_file = $this->cache_dir . '/win7_sidenav.json';
		$this->cache_csv_file = $this->cache_dir . '/';
		$this->info_file = $this->os_info_dir . 'windows_7.json';
		$this->info_file_base = $this->os_info_dir . 'windows_7_base.json';

		if (file_exists($this->info_file_base)) {
			if (!file_exists($this->info_file))
				copy($this->info_file_base, $this->info_file);
		} else {
			// @todo: fetch from GitHub?
		}

		if ($fetch_new_data) {
			// fetch old data before getting new data so we can compare
			$this->sidenav_json_cached = $this->load_cached_sidenav_json();
			$this->sidenav_json = $this->fetch_sidenav_json();
		} else {
			$this->sidenav_json_cached = $this->load_cached_sidenav_json();
			$this->sidenav_json = $this->sidenav_json_cached;
		}

		// decode the JSON
		$this->sidenav_json_cached_obj = json_decode($this->sidenav_json_cached);
		$this->sidenav_json_obj = json_decode($this->sidenav_json);

		if ($init) {
			$has_new_updates = $this->has_new_updates();

			if ($has_new_updates || true)
				$this->release_data = $this->find_data($this->sidenav_json_obj);

			if ($update_json && $this->release_data !== false) {
				$update_res = $this->update_json($this->release_data);
			}
		}
	}

	// find all data from JSON and HTML source
	public function find_data($json)
	{
		// find latest KB entry
		$this->data_url = $this->build_kb_url($this->find_latest_kb_id($json));

		// fetch new KB HTML
		$this->release_html = $this->fetch_version_html();

		// find CSV
		$this->csv_file_url = $this->find_csv_url($this->release_html);

		if ($this->csv_file_url === false)
			return false;

		// @todo: fetch/cache csv; find version info


		/*
		$release_data = $this->builds;
		foreach ($this->builds as $idx => $build_info) {
			$release_data[$idx] += $this->find_latest_version_data_by_version_name($build_info['version_name']);
		}

		return $release_data;
		*/
	}

	public function has_new_updates()
	{
		return $this->sidenav_json_cached_obj->details->publishedOn !== $this->sidenav_json_obj->details->publishedOn;
	}

	// find latest KB article ID from sidenav JSON
	public function find_latest_kb_id($json)
	{
		return $json->links[1]->articleId;
	}

	// build a proper KB article URL
	public function build_kb_url($kb_id)
	{
		return $this->kb_base_url . $kb_id . "/windows-7-update-kb$kb_id";
	}

	// find CSV url from HTML
	public function find_csv_url($html)
	{
		$csv_filename = substr($this->data_url, strrpos($this->data_url, 'kb') + 2) . '.csv';
		$ms_download_domain = 'download.microsoft.com';
		$pattern = '/\\\"(?<csv_url>https?:\/\/' . $ms_download_domain . '.+?\/' . $csv_filename . ')\\\"/';
		//$pattern = '/https?:\/\/.+?' . '\/' . $csv_filename . '/';
		$csv_matches = array();
		$res = preg_match($pattern, $html, $csv_matches);

		if ($res === false || $res === 0)
			return false;

		return $csv_matches['csv_url'];
	}

	// @todo: use for refactoring later?
	public function cache_file($file, $data = '')
	{
		$res = $this->write_file($this->cache_kb_html_file, $data, true);

		return $res;
	}

	public function cache_sidenav_json($json_string = '')
	{
		if (isset($json_string) && !empty($json_string)) {
			$json = $json_string;
		} elseif (isset($this->sidenav_json) && !empty($this->sidenav_json)) {
			$json = $this->sidenav_json;
		}
		$res = file_put_contents($this->cache_sidenav_file, $json);

		return $res;
	}

	public function fetch_sidenav_json($cache = true)
	{
		$ch = curl_init($this->kb_sidenav_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		if ($res === false)
			return false;

		if ($cache === true)
			$this->cache_sidenav_json($res);

		return $res;
	}

	public function load_cached_sidenav_json()
	{
		if (!file_exists($this->cache_sidenav_file))
			return false;

		$contents = file_get_contents($this->cache_sidenav_file);
		if ($contents === false)
			return false;

		return $contents;
	}

	public function cache_version_html($html_string = '')
	{
		if (isset($html_string) && !empty($html_string)) {
			$html = $html_string;
		} elseif (isset($this->release_html) && !empty($this->release_html)) {
			$html = $this->release_html;
		}
		$res = file_put_contents($this->cache_kb_html_file, $html);

		return $res;
	}

	public function fetch_version_html($cache = true)
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
		if (!file_exists($this->cache_kb_html_file))
			return false;

		$contents = file_get_contents($this->cache_kb_html_file);
		if ($contents === false)
			return false;

		return $contents;
	}

	public function cache_update_list_csv($csv_string = '')
	{
	}

	public function fetch_update_list_csv($cache = true)
	{
	}

	public function load_cached_update_list_csv()
	{
	}

	public function find_builds()
	{
		if (!isset($this->release_html) || empty($this->release_html))
			return false;

		$string = $this->release_html;
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

	public function find_latest_version_data_by_version_name($version_name)
	{
		if (!isset($this->release_html) || empty($this->release_html))
			return false;

		if (!isset($version_name))
			return false;

		$string = $this->release_html;

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
