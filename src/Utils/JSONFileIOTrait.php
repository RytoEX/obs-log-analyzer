<?php

namespace RytoEX\OBS\LogAnalyzer\Utils;

trait JSONFileIOTrait
{
	use \RytoEX\OBS\LogAnalyzer\Utils\FileIOTrait;

	public function read_json_file($file, $decode = true)
	{
		$json = $this->read_file($file);
		if ($json === false)
			return false;

		if ($decode === true) {
			$json_decoded = json_decode($json, true);
			if ($json_decoded === null)
				return false;

			$json = $json_decoded;
		}

		return $json;
	}

	/**
	 * Replace spaces with tabs.
	 *
	 * PHP's json_encode JSON_PRETTY_PRINT indents with 4-spaces instead of tabs.
	 * This replaces every 4-space indent with tabs.
	 *
	 * @param $json string The JSON string to tabify
	 *
	 * @return string A tabified JSON string
	 */
	public function tabify_json_string($json)
	{
		$pattern = '/^([\t ]*)( ){4}([\t ]*)(\S)/m';
		$replace = '$1	$3$4';
		$count = -1;
		do {
			$json = preg_replace($pattern, $replace, $json, -1, $count);
		} while ($count > 0);

		return $json;
	}

	public function tabify_json_file($file)
	{
		$json = $this->read_json_file($file, false);
		$json = $this->tabify_json_string($json);
		return $json;
	}

	public function write_json_file($file, $data, $overwrite = false, $tabify = false)
	{
		if (is_array($data)) {}
			$data = json_encode($data, JSON_PRETTY_PRINT);

		if ($tabify === true)
			$data = $this->tabify_json_string($data);

		$res = $this->write_file($file, $data, $overwrite);

		return $res;
	}
}
