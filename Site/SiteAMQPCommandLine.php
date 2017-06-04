<?php

/**
 * Optional helper class for building command-line interfaces for AMQP
 * workers
 *
 * This adds all the default options and arguments required by AMQP
 * workers so they do not need to be repeatedly defined in the individual
 * worker XML command-line interface files.
 *
 * Example usage:
 * <code>
 * <?php
 * $parser = SiteAMQPCommandLine::fromXMLFile($my_file);
 * ?>
 * </code>
 *
 * @package   Site
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteAMQPCommandLine
{
	// {{{ public static function fromXMLFile()

	/**
	 * Creates a command-line instance from the provided XML definition
	 *
	 * If the interface definition omits any of the required options and
	 * arguments for an AMQP worker, they are automatically added.
	 *
	 * @param string $filename the file containing the XML command-line
	 *                         interface definition.
	 *
	 * @return Console_CommandLine a command-line parser.
	 */
	public static function fromXMLFile($filename)
	{
		$parser = Console_CommandLine::fromXmlFile($filename);

		self::addDefaultOptions($parser);
		self::addDefaultArguments($parser);

		return $parser;
	}

	// }}}
	// {{{ public static function fromXMLString()

	/**
	 * Creates a command-line instance from the provided XML definition
	 *
	 * If the interface definition omits any of the required options and
	 * arguments for an AMQP worker, they are automatically added.
	 *
	 * @param string $string a string containing the XML command-line
	 *                       interface definition.
	 *
	 * @return Console_CommandLine a command-line parser.
	 */
	public static function fromXMLString($string)
	{
		$parser = Console_CommandLine::fromXmlString($string);

		self::addDefaultOptions($parser);
		self::addDefaultArguments($parser);

		return $parser;
	}

	// }}}
	// {{{ protected static function addDefaultOptions()

	/**
	 * Adds required default options to a command-line parser if they do not
	 * exist
	 *
	 * @param Console_CommandLine the command-line parser.
	 *
	 * @return void
	 */
	protected static function addDefaultOptions(Console_CommandLine $parser)
	{
		if (!isset($parser->options['verbose'])) {
			$parser->addOption(
				'verbose',
				array(
					'short_name'  => '-v',
					'long_name'   => '--verbose',
					'action'      => 'Counter',
					'default'     => 0,
					'description' => Site::_(
						'Set verbosity level. Use multiples for more detail '.
						'(e.g. "-vv".)'
					)
				)
			);
		}

		if (!isset($parser->options['port'])) {
			$parser->addOption(
				'port',
				array(
					'short_name'  => '-p',
					'long_name'   => '--port',
					'action'      => 'StoreInt',
					'default'     => 5672,
					'description' => Site::_(
						'Port of AMQP server. If not specified, port '.
						'5672 is used.'
					)
				)
			);
		}
	}

	// }}}
	// {{{ protected static function addDefaultArguments()

	/**
	 * Adds required default arguments to a command-line parser if they do not
	 * exist
	 *
	 * @param Console_CommandLine the command-line parser.
	 *
	 * @return void
	 */
	protected static function addDefaultArguments(Console_CommandLine $parser)
	{
		if (!isset($parser->arguments['address'])) {
			$parser->addArgument(
				'address',
				array(
					'optional'    => true,
					'default'     => '127.0.0.1',
					'description' => Site::_(
						'AMQP server address. If not specified, '.
						'127.0.0.1 is used.'
					)
				)
			);
		}
	}

	// }}}
}

?>
