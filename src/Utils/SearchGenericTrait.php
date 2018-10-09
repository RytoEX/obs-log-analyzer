<?php

namespace RytoEX\OBS\LogAnalyzer\Utils;


trait SearchGenericTrait
{
	/**
	 * @param $haystack string The string to search through.
	 * @param $value_marker string The marker that indicates the start of the desired substring.
	 * @param $end_marker string|null The marker that indicates the end of the desired substring.
	 * @param $opts array An array of options to alter search and return behavior.
	 * @return string|array|false Return a substring or array on success.  Return false on failure.
	 */
	public function find_string($haystack, $value_marker, $end_marker = null, array $opts = array('offset' => 0, 'trim' => 'b'))
	{
		// default opts, because the default array may be replaced
		// use a default offset of 0
		if (!isset($opts['offset'])) {
			$opts['offset'] = 0;
		}
		// trim both left and right sides by default
		if (!isset($opts['trim'])) {
			$opts['trim'] = 'b';
		}
		// use a special end marker of end-of-line (\n)
		if (!isset($opts['special_end_marker'])) {
			$opts['special_end_marker'] = 'EOL';
		}
		// by default, return the found substring
		// optionally, return:
		// - string
		// - value_start_pos
		// - value_end_pos
		if (!isset($opts['return'])) {
			$opts['return'] = array('string');
		}
		// @todo: finish implementation of search directions for start and end markers
		// search for $value_marker from the start or end of the $haystack
		if (!isset($opts['search_direction'])) {
			$opts['search_direction'] = 'ltr';
		}
		/*
		// maybe use array union?
		// $opts += (opts) ?
		$opts += array('offset' => 0,
			'trim' => 'b',
			'special_end_marker' => 'EOL',
			'return' => array('string'),
			'' => ''
		);
		*/

		if (!isset($value_marker) || $value_marker === '') {
			// If $value_marker is null or is an empty string, assume pos is 0
			$value_marker_pos = 0;
		} else {
			$value_marker_pos = strpos($haystack, $value_marker, $opts['offset']);
		}

		if (!isset($end_marker) && $opts['special_end_marker'] !== 'EOF') { // assume EOL
			// @todo: add support for \r\n (CRLF/Windows) line endings
			/*
			$value_end_pos = strpos($haystack, "\r\n", $value_marker_pos);
			if ($value_end_pos === false) {
				$value_end_pos = strpos($haystack, "\n", $value_marker_pos);
			}
			*/
			$value_end_pos = strpos($haystack, "\n", $value_marker_pos);
			if ($value_end_pos === false) {
				// Mac OS 9 line endings; not even supported?
				$value_end_pos = strpos($haystack, "\r", $value_marker_pos);
				//$value_end_pos = strpos($haystack, "\n", $value_marker_pos);
			}
			if ($value_end_pos === false) {
				// Probably a string with no EOL marker, assume last-pos
				$value_end_pos = strlen($haystack);
			}
		} elseif (!isset($end_marker) && $opts['special_end_marker'] === 'EOF') {
			// assume the end position is at the end of the entire string or end-of-file (EOF)
			$value_end_pos = strlen($haystack);
		} else {
			// search for the first end_marker after the value_marker or end_marker_offset
			if (isset($opts['end_marker_offset'])) {
				// if end_marker_offset is set, use it as the offset for finding end_marker
				$end_offset = $opts['end_marker_offset'];
			} else {
				// else, set end_offset to just after the value_marker
				$end_offset = $value_marker_pos + strlen($value_marker);
			}

			// find the position of the end_marker
			$value_end_pos = strpos($haystack, $end_marker, $end_offset);
		}

		// if we have both a start and end position, find the substring
		if ($value_marker_pos !== false && $value_end_pos !== false) {
			$value_start_pos = $value_marker_pos + strlen($value_marker);

			// find the actual value we're looking for
			$value = substr($haystack,
				$value_start_pos,
				$value_end_pos - $value_start_pos);
			if ($opts['trim'] === 'b') {
				$value = trim($value);
			} elseif ($opts['trim'] === 'l') {
				$value = ltrim($value);
			} elseif ($opts['trim'] === 'r') {
				$value = rtrim($value);
			}

			// if we only need to return the value, return it
			if (count($opts['return']) === 1 && $opts['return'][0] === 'string') {
				return $value;
			}

			// if we have a more complex return requirement, build it
			if (in_array('string', $opts['return'])) {
				$return['string'] = $value;
			}
			if (in_array('value_start_pos', $opts['return'])) {
				$return['value_start_pos'] = $value_start_pos;
			}
			if (in_array('value_end_pos', $opts['return'])) {
				$return['value_end_pos'] = $value_end_pos;
			}
			return $return;
		}

		return false;
	}

	/**
	 * Find a generic value in a string.
	 *
	 * Non-breaking space (nbsp, or (chr(0xC2).chr(0xA0))) is only used in log profiler lines, as far as I know
	 * @param $haystack string The string to search through.
	 * @param $value_name string The marker that indicates the start of the desired substring.
	 * @param $unit_string string The marker that indicates the end of the desired substring.
	 * @return string Return a substring on success.
	 */
	public function find_value($haystack, $value_name, $unit_string)
	{
		$value_pos = strpos($haystack, "$value_name=") + strlen($value_name) + 1;
		$unit_pos = strpos($haystack, $unit_string, $value_pos);
		$value_value = trim(substr($haystack, $value_pos, $unit_pos - $value_pos), chr(0xC2).chr(0xA0).' ');
		return $value_value;
	}
}
