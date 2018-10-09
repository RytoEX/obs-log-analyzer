<?php

namespace RytoEX\OBS\LogAnalyzer\Utils;

trait FileIOTrait
{
	public function delete_file($file)
	{
		if (!file_exists($file))
			return false;

		$res = unlink($file);

		return $res;
	}

	public function read_file($file)
	{
		if (!file_exists($file))
			return false;

		$data = file_get_contents($file);

		return $data;
	}

	public function write_file($file, $data, $overwrite = false)
	{
		if (file_exists($file) && $overwrite === false)
			return false;

		$res = file_put_contents($file, $data);

		return $res;
	}
}
