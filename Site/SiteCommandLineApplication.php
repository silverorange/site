<?php

require_once 'Site/SiteApplication.php';
require_once 'Site/exceptions/SiteException.php';
// require_once 'Site/SiteCommandLineArgument.php';

/**
 * An application designed to run on the command line
 *
 * This class handles the creating and parsing of command line arguments and
 * has the ability to display usage information.
 *
 * @package   Site
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteCommandLineApplication extends SiteApplication
{
	/**
	 * An array of {@link SiteCommandLineArgument} objects used by this
	 * application
	 *
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * The title of this application
	 *
	 * The title is displayed in error messages and in usage information.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Text describing the purpose of this application
	 *
	 * This is displayed in usage information.
	 *
	 * @var string
	 */
	protected $documentation;

	/**
	 * Creates a new command line application
	 *
	 * By default, a 'help' command line argument is added. This argument is
	 * set to display usage information.
	 *
	 * @param string $id a unique identifier for this application.
	 * @param string $title the title of this application.
	 * @param string $documentation optional text describing the purpose of
	 *                               this application.
	 *
	 * @throws SiteException if this application is not created in a command-
	 *                        line environment.
	 */
	public function __construct($id, $title, $documentation = null)
	{
		if (!isset($_SERVER['argv']))
			throw new SiteException('Command line applications must be run '.
				'the command line.');

		parent::__construct($id);

		$this->title = $title;
		$this->documentation = $documentation;
		$help_argument = new SiteCommandLineArgument(array('-?', '--help'),
			'displayUsage',
			Site::_('Displays this usage information and exits.'));

		$this->addCommandLineArgument($help_argument);
	}

	/**
	 * Adds a command line argument to this application
	 *
	 * Command line arguments may be added either when the class is used or
	 * in the class definition.
	 *
	 * For example, to create a command line argument accepting either '-f' or
	 * '--foo' that runs the foo() method, use the following:
	 *
	 * <code>
	 * $foo_argument = new SiteCommandLineArgument(array('-f', '--foo'), 'foo',
	 *     'Runs the foo() method.');
	 *
	 * $app->addCommandLineArgument($foo_argument);
	 * </code>
	 *
	 * @param SiteCommandLineArgument $argument the command line argument to
	 *                                           add.
	 */
	public function addCommandLineArgument(SiteCommandLineArgument $argument)
	{
		$this->arguments[] = $argument;
	}

	/**
	 * Displays usage information for this command line application
	 */
	public function displayUsage()
	{
		echo $this->title, "\n\n";
		if ($this->documentation !== null)
			echo $this->documentation, "\n\n";

		echo Site::_('OPTIONS'), "\n";

		foreach ($this->arguments as $argument) {
			$argument->displayUsage();
		}

		exit(0);
	}

	/**
	 * Automatically parses and interprets command line arguments of this
	 * application
	 *
	 * @see SiteCommandLineApplication::addCommandLineArgument()
	 */
	protected function parseCommandLineArguments()
	{
		$reflector = new ReflectionClass(get_class($this));

		$args = $_SERVER['argv'];
		$num_args = count($args);
		for ($i = 1; $i < $num_args; $i++) {
			$found_argument = false;
			foreach ($this->arguments as $argument) {
				$argument_arguments = array();
				if (in_array($args[$i], $argument->getNames())) {
					foreach ($argument->getArguments() as $argument_argument) {
						if (isset($args[$i + 1]) && $args[$i + 1][0] !== '-') {
							$i++;
							if ($argument_argument->validate($args[$i])) {
								$argument_arguments[] = $args[$i];
							} else {
								echo $this->title, ": ",
									$argument_argument->getErrorMessage(), "\n";

								exit(1);
							}
						} elseif ($argument_argument->hasDefault()) {
							$argument_arguments[] =
								$argument_argument->getDefault();
						} else {
							echo $this->title, ": ",
								$argument_argument->getErrorMessage(), "\n";

							exit(1);
						}
					}

					if (!$reflector->hasMethod($argument->getMethod()))
						throw new SiteException('Application argument calls '.
							'undefined method.');

					$method = $reflector->getMethod($argument->getMethod());
					if ($argument->hasArgument()) {
						$method->invokeArgs($this, $argument_arguments);
					} else {
						if ($method->getNumberOfRequiredParameters() > 0)
							$method->invoke($this, 'true');
						else
							$method->invoke($this);
					}

					$found_argument = true;
				}
			}
			if (!$found_argument) {
				printf(Site::_("%s: unknown command line argument '%s'"),
					$this->title, $args[$i]);

				echo "\n";

				exit(1);
			}
		}
	}
}

require_once 'Site/SiteObject.php';
// require_once 'Site/SiteCommandLineArgumentArgument.php';

class SiteCommandLineArgument extends SiteObject
{
	protected $documentation;
	protected $names = array();
	protected $arguments = array();

	public function __construct($names, $method, $documentation)
	{
		$this->names = $names;
		$this->method = $method;
		$this->documentation = $documentation;
	}

	public function &getNames()
	{
		return $this->names;
	}

	public function &getArguments()
	{
		return $this->arguments;
	}

	public function hasArgument()
	{
		return (count($this->arguments) > 0);
	}

	public function addArgument($type, $error_message, $default = null)
	{
		$this->arguments[] = new SiteCommandLineArgumentArgument($type,
			$error_message, $default);
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function getDocumentation()
	{
		return $this->documentation;
	}

	public function displayUsage()
	{
		echo implode(', ', $this->names), "\n",
			"   ", $this->documentation, "\n\n";
	}
}

require_once 'Site/SiteObject.php';

class SiteCommandLineArgumentArgument extends SiteObject
{
	protected $error_message;
	protected $type;
	protected $default;

	public function __construct($type, $error_message, $default)
	{
		$this->type = $type;
		$this->error_message = $error_message;
		$this->default = $default;
	}

	public function getErrorMessage()
	{
		return $this->error_message;
	}

	public function hasDefault()
	{
		return ($this->default !== null);
	}

	public function getDefault()
	{
		return $this->default;
	}

	public function getType()
	{
		return $this->type;
	}

	public function validate($value)
	{
		$valid = false;

		switch ($this->type) {
		case 'integer':
		case 'int':
			if (is_numeric($value) && strcmp((int)$value, $value) == 0)
				$valid = true;

			break;
		case 'double':
		case 'float':
			if (is_numeric($value))
				$valid = true;

			break;
		case 'string':
		default:
			$valid = true;
			break;
		}

		return $valid;
	}
}

?>
