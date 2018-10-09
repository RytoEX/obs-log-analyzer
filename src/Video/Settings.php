<?php

namespace RytoEX\OBS\LogAnalyzer\Video;

class Settings
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;

	// resolutions are stored as arrays
	private $base_resolution = array();
	private $output_resolution = array();

	// "common FPS" according to OBS
	private $common_fps = array(10, 20, 24, 29.97, 30, 48, 59.94, 60);

	// default YUV settings
	public $yuv_color_space_default = '601';
	public $yuv_color_range_default = 'Partial';

	public $raw_string;
	public $downscale_filter;
	public $fps_num;
	public $fps_string;
	public $format;
	public $yuv_mode;
	public $yuv_color_space;
	public $yuv_color_range;
	public $is_format_nonstandard;
	public $is_format_default;
	public $is_fps_common;
	public $is_output_scaled;
	public $is_output_upscaled;

	public function __construct($raw_string = null, $auto_process = true)
	{
		if (isset($raw_string)) {
			$this->raw_string = $raw_string;

			if ($auto_process === true) {
				//$this->process();
				// causes constructor to return result of process() instead of returning self
			}
		}
	}

	public function process()
	{
		if (!isset($this->raw_string) && !isset($this->downscale_filter)) {
			return false;
		}

		if (isset($this->raw_string)) {
			$haystack = $this->raw_string;
			$this->set_base_resolution($this->find_base_resolution());
			$this->set_output_resolution($this->find_output_resolution());
			$this->downscale_filter = $this->find_downscale_filter();
			$this->fps_string = $this->find_fps();
			$this->fps_num = $this->calc_fps_num();
			$this->format = $this->find_format();
			$this->yuv_mode = $this->find_yuv_mode();
			$this->yuv_color_space = $this->find_yuv_color_space();
			$this->yuv_color_range = $this->find_yuv_color_range();
		}

		if (!isset($this->downscale_filter)) {
			return false;
		}

		$this->is_output_scaled = $this->is_output_scaled();
		$this->is_output_upscaled = $this->is_output_upscaled();
		$this->is_format_default = $this->is_format_default();
		$this->is_fps_common = $this->is_fps_common();

		return true;
	}

	/*
	 * @return int|float
	 */
	public function calc_fps_num($numerator = null, $denominator = null)
	{
		if (isset($numerator, $denominator)) {
			$fps_numer = $numerator;
			$fps_denom = $denominator;
		} else {
			if (!isset($this->fps_string))
				$this->fps_string = $this->find_fps();

			$div_sym_pos = strpos($this->fps_string, '/');
			$fps_numer = substr($this->fps_string, 0, $div_sym_pos);
			$fps_denom = substr($this->fps_string, $div_sym_pos+1);
		}

		$fps_num = $fps_numer / $fps_denom;
		return $fps_num;
	}

	/*
	 * @return string
	 */
	public function get_fps()
	{
		if (isset($this->fps_string))
			$string = $this->fps_string;
		else
			$string = $this->find_fps();

		return $this->fps_string;
	}

	/*
	public function get_fps_num()
	{
		$div_sym_pos = strpos($this->fps_string, '/');
		$fps_numer = substr($this->fps_string, 0, $div_sym_pos);
		$fps_denom = substr($this->fps_string, $div_sym_pos+1);
		$fps_num = $fps_numer / $fps_denom;
		return $fps_num;
	}
	*/
	public function get_fps_num()
	{
		return $this->fps_num;
	}

	public function get_base_resolution()
	{
		return $this->base_resolution['string'];
	}

	public function get_base_resolution_width()
	{
		return $this->base_resolution['width'];
	}

	public function get_base_resolution_height()
	{
		return $this->base_resolution['height'];
	}

	public function get_output_resolution()
	{
		return $this->output_resolution['string'];
	}

	public function get_output_resolution_width()
	{
		return $this->output_resolution['width'];
	}

	public function get_output_resolution_height()
	{
		return $this->output_resolution['height'];
	}

	public function set_base_resolution($base_res)
	{
		$this->base_resolution['string'] = $base_res;
		$this->base_resolution['width'] = $this->find_resolution_width($base_res);
		$this->base_resolution['height'] = $this->find_resolution_height($base_res);
	}

	public function set_output_resolution($output_res)
	{
		$this->output_resolution['string'] = $output_res;
		$this->output_resolution['width'] = $this->find_resolution_width($output_res);
		$this->output_resolution['height'] = $this->find_resolution_height($output_res);
	}

	public function find_base_resolution($haystack = null)
	{
		if (!isset($haystack) && isset($this->raw_string))
			$haystack = $this->raw_string;

		return $this->find_string($haystack, ': 	base resolution:', null);
	}

	public function find_output_resolution($haystack = null)
	{
		if (!isset($haystack) && isset($this->raw_string))
			$haystack = $this->raw_string;

		return $this->find_string($haystack, ': 	output resolution:', null);
	}

	public function find_resolution_height($resolution_string)
	{
		$height = substr($resolution_string, strpos($resolution_string, 'x')+1);
		return $height;
	}

	public function find_resolution_width($resolution_string)
	{
		$width = substr($resolution_string, 0, strpos($resolution_string, 'x'));
		return $width;
	}

	public function find_downscale_filter($haystack = null)
	{
		if (!isset($haystack) && isset($this->raw_string))
			$haystack = $this->raw_string;

		return $this->find_string($haystack, ': 	downscale filter:', null);
	}

	public function find_fps($haystack = null)
	{
		if (!isset($haystack) && isset($this->raw_string))
			$haystack = $this->raw_string;

		return $this->find_string($haystack, ': 	fps:', null);
	}

	public function find_format($haystack = null)
	{
		if (!isset($haystack) && isset($this->raw_string))
			$haystack = $this->raw_string;

		return $this->find_string($haystack, ': 	format:', null);
	}

	public function find_yuv_mode($haystack = null)
	{
		if (!isset($haystack) && isset($this->raw_string))
			$haystack = $this->raw_string;

		return $this->find_string($haystack, ': 	YUV mode:', null);
	}

	public function find_yuv_color_space($yuv_mode = null)
	{
		if (!isset($yuv_mode) && isset($this->yuv_mode)) {
			$yuv_mode = $this->yuv_mode;
		} elseif (!isset($yuv_mode) && !isset($this->yuv_mode)) {
			return false;
		}

		return $this->find_string($yuv_mode, null, '/');
	}

	public function find_yuv_color_range($yuv_mode = null)
	{
		if (!isset($yuv_mode) && isset($this->yuv_mode)) {
			$yuv_mode = $this->yuv_mode;
		} elseif (!isset($yuv_mode) && !isset($this->yuv_mode)) {
			return false;
		}

		return $this->find_string($yuv_mode, '/', null);
	}

	//public function is_format_nonstandard()
	// swap this around to is_standard?
	public function is_format_default()
	{
		if (isset($this->is_format_default)) {
			return $this->is_format_default;
		}

		if (isset($this->format) && $this->format === 'NV12') {
			return true;
		}
		return false;
	}

	public function is_fps_common()
	{
		if (isset($this->is_fps_common)) {
			return $this->is_fps_common;
		}

		if (isset($this->fps_string)) {
			$fps = $this->get_fps_num();
			if (in_array($fps, $this->common_fps)) {
				return true;
			}
		}
		return false;
	}

	// @todo: backwards compatibility, should be able to remove
	public function is_fps_standard()
	{
		return $this->is_fps_common();
	}

	public function is_output_scaled()
	{
		if (isset($this->is_output_scaled)) {
			return $this->is_output_scaled;
		}

		if (isset($this->base_resolution['string'],
			$this->output_resolution['string']) &&
			($this->base_resolution['string'] !== $this->output_resolution['string'])) {
			return true;
		}
		return false;
	}

	public function is_output_upscaled()
	{
		if (isset($this->is_output_upscaled)) {
			return $this->is_output_upscaled;
		}

		if (isset($this->base_resolution['string'],
			$this->base_resolution['height'],
			$this->base_resolution['width'],
			$this->output_resolution['string'],
			$this->output_resolution['height'],
			$this->output_resolution['width']) &&
			($this->output_resolution['height'] > $this->base_resolution['height']) &&
			($this->output_resolution['width'] > $this->base_resolution['width'])) {
			return true;
		}
		return false;
	}

	public function is_yuv_color_range_default()
	{
		if (!isset($this->yuv_color_range))
			return false;

		if ($this->yuv_color_range !== $this->yuv_color_range_default)
			return false;
		else
			return true;
	}

	public function is_yuv_color_space_default()
	{
		if (!isset($this->yuv_color_space))
			return false;

		if ($this->yuv_color_space !== $this->yuv_color_space_default)
			return false;
		else
			return true;
	}

	public function get_print_string()
	{
		$print = "base resolution: " . $this->get_base_resolution() . "\n";
		$print .= "output resolution: " . $this->get_output_resolution() . "\n";
		$print .= "downscale filter: " . $this->downscale_filter . "\n";
		$print .= "fps: " . $this->fps_string . "\n";
		$print .= "format: " . $this->format . "\n";
		$print .= "YUV mode: " . $this->yuv_mode . "\n";
		return $print;
	}
}
