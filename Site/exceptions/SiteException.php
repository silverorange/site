<?php

require_once 'Swat/exceptions/SwatException.php';

/**
 * An exception in Site package
 *
 * @package   Site
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteException extends SwatException
{
	/**
	 * Gets this exception as a nicely formatted XHTML fragment
	 *
	 * This is nice for debugging errors on a staging server.
	 *
	 * @return string this exception formatted as XHTML.
	 */
	public function toXHTML()
	{
		ob_start();

		$this->displayStyleSheet();

		echo '<div class="site-exception">';

		printf('<h3>Uncaught Exception: %s</h3>'.
				'<div class="site-exception-body">'.
				'Message:<div class="site-exception-message">%s</div>'.
				'Thrown in file <strong>%s</strong> '.
				'on line <strong>%s</strong>.<br /><br />',
				get_class($this),
				nl2br($this->getMessage()),
				$this->getFile(),
				$this->getLine());

		echo 'Stack Trace:<br /><dl>';
		$trace = $this->getTrace();
		$count = count($trace);

		foreach ($trace as $entry) {

			if (array_key_exists('args', $entry))
				$arguments = htmlentities($this->getArguments($entry['args']), null, 'UTF-8');
			else
				$arguments = '';

			printf('<dt>%s.</dt><dd>In file <strong>%s</strong> '.
				'line&nbsp;<strong>%s</strong>.<br />Method: '.
				'<strong>%s%s%s(</strong>%s<strong>)</strong></dd>',
				--$count,
				$entry['file'],
				$entry['line'],
				array_key_exists('class', $entry) ? $entry['class'] : '',
				array_key_exists('type', $entry) ? $entry['type'] : '',
				$entry['function'],
				$arguments);
		}

		echo '</dl></div></div>';

		return ob_get_clean();
	}

	/**
	 * Displays style sheet required for XHMTL exception formatting
	 *
	 * @todo separate this into a separate file
	 */
	private function displayStyleSheet()
	{
		echo '<style>';
		echo ".site-exception { border: 1px solid #3d4; margin: 1em; font-family: sans-serif; }\n";
		echo ".site-exception h3 { background: #5e6; margin: 0; padding: 5px; border-bottom: 2px solid #3d4; color: #fff; }\n";
		echo ".store-exception-body { padding: 0.8em; }\n";
		echo ".store-exception-message { margin-left: 2em; padding: 1em; }\n";
		echo ".store-exception dt { float: left; margin-left: 1em; }\n";
		echo ".store-exception dd { margin-bottom: 1em; }\n";
		echo '</style>';
	}
}

?>
