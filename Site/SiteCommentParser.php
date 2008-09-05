<?php

require_once 'Swat/SwatString.php';

/**
 * Parses the bodytext of a comment
 *
 * Allowed tags are '<a href="">', '<strong>', '<em>' and '<code>'. Missing
 * closing tags are automatically closed and closing tags with missing opening
 * tags are displayed as plain text.
 *
 * Example:
 *
 * <code>
 * <?php
 * $comment = ' ... ';
 * echo SiteCommentParser::parse($comment);
 * ?>
 * </code>
 *
 * The first method, parse(), cleans and filters any included inline tags.
 * The second method, toXhtml(), genetrates XHTML output in paragraphs.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentParser
{
	// {{{ protected static properties

	/**
	 * @var array
	 */
	protected static $tag_stack = array();

	/**
	 * @var array
	 */
	protected static $opening_tags = array(
		'a',
		'em',
		'strong',
		'code',
	);

	/**
	 * @var array
	 */
	protected static $closing_tags = array(
		'na',
		'nem',
		'nstrong',
		'ncode',
	);

	/**
	 * @var boolean
	 */
	protected static $use_mb_string = null;

	// }}}
	// {{{ public static function parse()

	/**
	 * @param string $comment
	 *
	 * @return string
	 */
	public static function parse($comment)
	{
		if (self::$use_mb_string === null) {
			self::$use_mb_string = (extension_loaded('mbstring') &&
				(ini_get('mbstring.func_overload') & 2) === 2);
		}

		self::$tag_stack = array();

		ob_start();
		self::parseInternal($comment);
		return ob_get_clean();
	}

	// }}}
	// {{{ public static function toXHTML()

	/**
	 * @param string $comment
	 *
	 * @return string
	 */
	public static function toXHTML($comment)
	{
		$comment = self::parse($comment);

		$comment = str_replace("\r\n", "\n", $comment);
		$comment = str_replace("\r",   "\n", $comment);
		$comment = preg_replace('/[\x0a\s]*\n\n[\x0a\s]*/s', '</p><p>',
			$comment);

		$comment = preg_replace('/[\x0a\s]*\n[\x0a\s]*/s', '<br />',
			$comment);

		$comment = '<p>'.$comment.'</p>';

		return $comment;
	}

	// }}}
	// {{{ protected static function startTag()

	protected static function startTag($data, $tag_name)
	{
		array_push(self::$tag_stack, $tag_name);
		echo $data;
	}

	// }}}
	// {{{ protected static function endTag()

	protected static function endTag($data, $tag_name)
	{
		if (end(self::$tag_stack) === $tag_name) {
			array_pop(self::$tag_stack);
			echo $data;
		} else {
			echo SwatString::minimizeEntities($data);
		}
	}

	// }}}
	// {{{ protected static function characterData()

	protected static function characterData($data)
	{
		echo SwatString::minimizeEntities($data);
	}

	// }}}
	// {{{ protected static function parseInternal()

	protected static function parseInternal($comment)
	{
		// expression to get all allowed tags
		$tokens = '/
			(
				<(?P<a>a)
					(?: title="[^"]+?")?
					\ href="http[^"]+?"
					(?: title="[^"]+?")?
				>
				|
				<(?P<em>em)>
				|
				<(?P<strong>strong)>
				|
				<(?P<code>code)>
				|
				<\/(?P<na>a)>
				|
				<\/(?P<nem>em)>
				|
				<\/(?P<nstrong>strong)>
				|
				<\/(?P<ncode>code)>
			)
			/uix';

		$matches = array();
		// Note: PHP PCRE always returns offsets in bytes, not characters
		preg_match_all($tokens, $comment, $matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		$offset = 0;
		foreach ($matches as $match) {

			// get leading character data before tag
			if ($match[0][1] !== $offset) {
				$data = self::getByteSubstring($comment, $offset,
					$match[0][1]);

				self::characterData($data);
			}

			// check if it is an opening tag
			foreach (self::$opening_tags as $tag) {
				if (array_key_exists($tag, $match) && $match[$tag][1] != -1) {
					self::startTag($match[0][0], $tag);
					break;
				}
			}

			// check if it is a closing tag
			foreach (self::$closing_tags as $tag) {
				if (array_key_exists($tag, $match) && $match[$tag][1] != -1) {
					$tag = substr($tag, 1); // strip leading 'n'
					self::endTag($match[0][0], $tag);
					break;
				}
			}

			$offset = $match[0][1] + self::getByteLength($match[0][0]);
		}

		// get trailing character data
		$length = self::getByteLength($comment);
		if ($offset < $length) {
			$data = self::getByteSubstring($comment, $offset, $length);
			self::characterData($data);
		}

		// close unclosed tags
		while (count(self::$tag_stack) > 0) {
			$tag = array_pop(self::$tag_stack);
			echo '</', $tag, '>';
		}
	}

	// }}}
	// {{{ protected static function getByteSubstring()

	protected static function getByteSubstring($string, $from, $to)
	{
		$start  = $from;
		$length = $to - $from;

		if (self::$use_mb_string) {
			return mb_substr($string, $start, $length, '8bit');
		}

		return substr($string, $start, $length);
	}

	// }}}
	// {{{ protected static function getByteLength()

	protected static function getByteLength($string)
	{
		if (self::$use_mb_string) {
			return mb_strlen($string, '8bit');
		}

		return strlen($string);
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Prevent instantiation of static class
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
