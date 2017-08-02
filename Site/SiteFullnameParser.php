<?php

/**
 * Parses a fullname into a first and last name
 *
 * @package   Site
 * @copyright 2005-2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteFullnameParser
{
	// {{{ class constants

	/**
	 * When splitting a name with no spaces, return an empty first name
	 */
	const ALWAYS_LAST = 0;

	/**
	 * When splitting a name with no spaces, return an empty last name
	 */
	const ALWAYS_FIRST = 1;

	/**
	 * When splitting a name with no spaces, return a a first and last name
	 * by splitting the string in the middle
	 */
	const ALWAYS_BOTH = 2;

	// }}}
	// {{{ public static function parse()

	/**
	 * Parses a fullname into a first and last name
	 *
	 * @param string  $fullname the full name to parse.
	 * @param integer $mode     optional. The behavior when the provided name
	 *                          contains no whitespace. One of
	 *                          {@link SiteFullnameParser::ALWAYS_FIRST},
	 *                          {@link SiteFullnameParser::ALWAYS_LAST}, or
	 *                          {@link SiteFullnameParser::ALWAYS_BOTH}.
	 *                          Defaults to ALWAYS_FIRST.
	 *
	 * @return array an associative array containing elements 'first' and
	 *               'last' for the parsed parts of the full name.
	 */
	public static function parse($fullname, $mode = self::ALWAYS_FIRST)
	{
		$length = mb_strlen($fullname);

		$midpoint = intval(floor($length / 2));

		// get space closest to the middle of the string
		$left_pos  = mb_strrpos(
			$fullname,
			' ',
			-$length + $midpoint
		);

		$right_pos = mb_strpos($fullname, ' ', $midpoint);

		if ($left_pos === false && $right_pos === false) {
			switch ($mode) {
			case self::ALWAYS_BOTH:
				$pos = $midpoint;
				break;
			case self::ALWAYS_LAST:
				$pos = 0;
				$break;
			case self::ALWAYS_FIRST:
			default:
				$pos = $length - 1;
				break;
			}
		} elseif ($left_pos === false) {
			$pos = $right_pos;
		} elseif ($right_pos === false) {
			$pos = $left_pos;
		} elseif (($midpoint - $left_pos) <= ($right_pos - $midpoint)) {
			$pos = $left_pos;
		} else {
			$pos = $right_pos;
		}

		// Split name into first and last parts. We need to trim the last name
		// result since it can include the space character used to split.
		$first_name = mb_substr($fullname, 0, $pos);
		$last_name = ltrim(mb_substr($fullname, $pos));

		return array(
			'first' => $first_name,
			'last' => $last_name,
		);
	}

	// }}}
	// {{{ private function __construct()

	private function __construct()
	{
	}

	// }}}
}

?>
