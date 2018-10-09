<?php

namespace RytoEX\OBS\LogAnalyzer\Utils;

// Validate, sanitize, and build file URLs for webhosted OBS logs
// Does not handle downloading log file
class UrlHandler
{
	// Currently handling URLs for text files from these providers
	public static $github_gist_base_url = 'https://gist.github.com/';
	public static $pastebin_base_url = 'https://pastebin.com/'; //7jwP1Nim
	public static $hastebin_base_url = 'https://hastebin.com/';
	public static $dropbox_base_url = 'https://www.dropbox.com/';

	// URLs that probably don't need additional handling
	public static $obsproject_base_url = 'https://www.obsproject.com/logs/';
	public static $discord_base_url = 'https://cdn.discordapp.com/attachments/';

	public $original_url;
	public $sanitized_url;
	public $final_url;
	public $host;
	public $is_valid;
	public $is_sanitized;
	public $contents;


	public function __construct($url = '', $init = true)
	{
		$this->original_url = $url;

		if ($init === true) {
			$url = $this->sanitize($this->original_url);
			if ($this->validate($this->original_url) && $url !== false) {
				$this->sanitized_url = $url;
				$this->is_valid = true;
				$this->is_sanitized = true;
				$this->final_url = $this->process();
			} else {
				$this->is_valid = false;
				$this->is_sanitized = false;
			}
		}
	}

	// @todo: make sure FILTER_VALIDATE_URL exists
	// @todo: possibly use FILTER_FLAG_SCHEME_REQUIRED & FILTER_FLAG_HOST_REQUIRED & FILTER_FLAG_PATH_REQUIRED
	// @todo: limit URL to HTTP/HTTPS
	public function validate($url = '')
	{
		if (empty($url) && isset($this->original_url))
			$url = $this->original_url;

		$data = filter_var($url, FILTER_VALIDATE_URL);
		if ($data === false)
			return false;

		return true;
	}

	// @todo: make sure FILTER_SANITIZE_URL exists
	public function sanitize($url = '')
	{
		if (empty($url) && isset($this->original_url))
			$url = $this->original_url;

		$data = filter_var($url, FILTER_SANITIZE_URL);
		if ($data === false)
			return false;

		return $data;
	}

	/*
	 * @return string|false
	 */
	public function process($url = '')
	{
		if (empty($url) && isset($this->sanitized_url)) {
			$url = $this->sanitized_url;
		} elseif (empty($url) && !isset($this->sanitized_url)) {
			return false;
		}

		// Currently handling URLs for text files from these providers
		$github_gist_base_url = 'https://gist.github.com/';
		$pastebin_base_url = 'https://pastebin.com/'; //7jwP1Nim
		$hastebin_base_url = 'https://hastebin.com/';
		$dropbox_base_url = 'https://www.dropbox.com/';

		// Process the URL as needed to get a URL to raw text data
		// @todo: switch to GitHub API v4
		if ($github_gist_base_url === substr($url, 0, strlen($github_gist_base_url))) {
			$this->host = $github_gist_base_url;
			// setup a stream context
			$opts = [
				'http' => [
					'method' => 'GET',
					'header' => [
						'User-Agent: PHP'
					]
				]
			];
			$context = stream_context_create($opts);

			// GitHub API URL
			$github_api_url = 'https://api.github.com/gists' . substr($url, strrpos($url, '/'));

			// Get contents of API request
			if (ini_get('allow_url_fopen')) {
				$contents = file_get_contents($github_api_url,
					false,
					$context);
			} elseif(extension_loaded('curl')) {
				$ch = curl_init($github_api_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 'PHP');
				$contents = curl_exec($ch);
				curl_close($ch);
			} else {
				// uh-oh
			}
			unset($context);

			// Get a JSON array/object
			$gist_json_obj = json_decode($contents);
			$gist_files = $gist_json_obj->files;
			$gist_files_count = count($gist_files);
			$gist_file_obj = null;

			// I don't know why there would be more than 1 file in an OBS uploaded gist.
			if ($gist_files_count === 1) {
				$gist_file_obj = current($gist_files);
			} elseif ($gist_files_count > 1) {
				// Break out?  Do nothing?  This shouldn't happen anyway.
				/*foreach ($gist_json_obj->files as $key => $value) {
					$gist_file_obj = $value;
				}*/
			}

			// files larger than 10MB are truncated and require fetching the content from the raw_url
			// https://developer.github.com/v3/gists/#truncation
			if ($gist_file_obj->truncated === true && $gist_file_obj->size <= 10485760) {
				$url = $gist_file_obj->raw_url;
			} elseif ($gist_file_obj->truncated === false) {
				// load $gist_file_obj->content ?
				// might just be easier to set $url for later
				$url = $gist_file_obj->raw_url;
			} elseif ($gist_file_obj->truncated === true && $gist_file_obj->size > 10485760) {
				// would have to git clone with git_pull_url
			}
		} elseif ($pastebin_base_url === substr($url, 0, strlen($pastebin_base_url))) {
			$this->host = $pastebin_base_url;
			if (strpos($url, 'raw') === false) {
				$pastebin_id = substr($url, strrpos($url, '/') + 1);
				$url = $pastebin_base_url . 'raw/' . $pastebin_id;
			}
		} elseif ($hastebin_base_url === substr($url, 0, strlen($hastebin_base_url))) {
			$this->host = $hastebin_base_url;
			if (strpos($url, 'raw') === false) {
				$hastebin_id = substr($url, strrpos($url, '/') + 1);
				$url = $hastebin_base_url . 'raw/' . $hastebin_id;
			}
		} elseif ($dropbox_base_url === substr($url, 0, strlen($dropbox_base_url))) {
			$this->host = $dropbox_base_url;
			$count = 0;
			$url = str_ireplace('https://www.dropbox.com', 'https://dl.dropbox.com', $url, $count);
			if ($count > 1) {
				// string shouldn't occur more than once
			}
			$url = str_ireplace(array('?dl=0', '?dl=1'), '', $url, $count);
			if ($count > 1) {
				// string shouldn't occur more than once
			}
		}

		return $url;
	}

	/*
	 * @return string|false
	 */
	public function build_html_link($url = '', $text = '')
	{
		if (empty($url) && isset($this->final_url)) {
			$url = $this->final_url;
		} elseif (empty($url) && !isset($this->final_url)) {
			return false;
		}

		if (empty($text))
			$text = $url;

		return "<a href=\"$url\">$text</a>";
	}

	/*
	 * @return string|false
	 */
	public function build_query_string($url = '')
	{
		if (empty($url) && isset($this->final_url)) {
			$url = $this->final_url;
		} elseif (empty($url) && !isset($this->final_url)) {
			return false;
		}

		return '?url=' . rawurlencode($url);
	}

	public function build_analyzer_link($base_url, $query_string)
	{
		return $base_url . $query_string;
	}

	public function is_host_hastebin()
	{
		if ($this->host === self::$hastebin_base_url) {
			return true;
		} else {
			return false;
		}
	}
}
