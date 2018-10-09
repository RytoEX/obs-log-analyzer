<?php

namespace RytoEX\OBS\LogAnalyzer\Video;

class Output
{
	use \RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait;

	public $raw_string;
	public $number;
	public $pos = array();
	public $size = array();
	public $attached;

	public function __construct($string)
	{
		if (!empty($string)) {
			$this->raw_string = $string;
			$this->number = $this->find_number($string);
			$this->set_pos($this->find_pos($string));
			$this->set_size($this->find_size($string));
			$this->attached = $this->find_attached($string);
		}
	}

	public function find_number($string)
	{
		// Handle a string like this by default
		// ': 	  output 1: pos={0, 0}, size={1920, 1080}, attached=true'
		$output_marker = ': 	  output ';
		$output_num = $this->find_string($string, $output_marker, ':');

		if ($output_num === false) {
			// Handle a string without 'output'
			// '1: pos={0, 0}, size={1920, 1080}, attached=true'
			$output_marker = '';
			$output_num = $this->find_string($string, $output_marker, ':');
		}
		return $output_num;
	}

	public function find_pos($string)
	{
		$pos_marker = 'pos=';
		$pos = $this->find_string($string, $pos_marker, ', size');

		return $pos;
	}

	public function find_size($string)
	{
		$size_marker = 'size=';
		$size = $this->find_string($string, $size_marker, ', attached');
		return $size;
	}

	public function find_attached($string)
	{
		$attached_marker = 'attached=';
		$attached = $this->find_string($string, $attached_marker);

		return $attached;
	}

	public function find_x($string)
	{
		$x = substr($string, strpos($string, '{')+1, strpos($string, ',')-1);
		return $x;
	}

	public function find_y($string)
	{
		$y = trim(substr($string, strpos($string, ',')+1), ' }');
		return $y;
	}

	public function get_number()
	{
		return $this->number;
	}

	public function get_pos()
	{
		return $this->pos;
	}

	public function get_pos_x()
	{
		return $this->pos['x'];
	}

	public function get_pos_y()
	{
		return $this->pos['y'];
	}

	public function get_size()
	{
		return $this->size;
	}

	public function get_size_x()
	{
		return $this->size['x'];
	}

	public function get_size_y()
	{
		return $this->size['y'];
	}

	public function get_attached()
	{
		return $this->attached;
	}

	public function get_print_string()
	{
		$print = "output " . $this->number . ": ";
		$print .= "pos=" . $this->pos['string'] . ", ";
		$print .= "size=" . $this->size['string'] . ", ";
		$print .= "attached=" . $this->attached . "\n";
		return $print;
	}

	public function set_pos($pos)
	{
		$this->pos['string'] = $pos;
		$this->pos['x'] = $this->find_x($pos);
		$this->pos['y'] = $this->find_y($pos);
	}

	public function set_size($size)
	{
		$this->size['string'] = $size;
		$this->size['x'] = $this->find_x($size);
		$this->size['y'] = $this->find_y($size);
	}
}
