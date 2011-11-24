<?php

require_once 'Site/dataobjects/SiteMedia.php';

/**
 * A BOTR-specific media object
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMedia extends SiteMedia
{
	// {{{ public properties

	/**
	 * BOTR key
	 *
	 * @var string
	 */
	public $key;

	// }}}
	// {{{ public function getHumanFileType()

	public function getHumanFileType($encoding_shortname = null)
	{
		if ($encoding_shortname === null) {
			$binding = $this->getLargestVideoEncodingBinding();

			if ($binding === null) {
				throw new SiteException(sprintf(
					'Encoding “%s” does not exist for media “%s”.',
						$encoding_shortname, $this->id));
			}

			$file_type = $binding->getHumanFileType();
		} else {
			$file_type = parent::getHumanFileType($encoding_shortname);
		}

		return $file_type;
	}

	// }}}
	// {{{ public function getFormattedFileSize()

	public function getFormattedFileSize($encoding_shortname = null)
	{
		if ($encoding_shortname === null) {
			$binding = $this->getLargestVideoEncodingBinding();

			if ($binding === null) {
				throw new SiteException(sprintf(
					'Encoding “%s” does not exist for media “%s”.',
						$encoding_shortname, $this->id));
			}

			$file_size = $binding->getFormattedFileSize();
		} else {
			$file_size = parent::getFormattedFileSize($encoding_shortname);
		}

		return $file_size;
	}

	// }}}
	// {{{ public function getLargestVideoEncodingBinding()

	public function getLargestVideoEncodingBinding()
	{
		$largest = null;

		foreach ($this->encoding_bindings as $binding) {
			if ($largest === null) {
				$largest = $binding;
			}

			if ($binding->width > $largest->width) {
				$largest = $binding;
			}
		}

		return $largest;
	}

	// }}}
	// {{{ public function getSmallestVideoEncodingBinding()

	public function getSmallestVideoEncodingBinding()
	{
		$smallest = null;

		foreach ($this->encoding_bindings as $binding) {
			if ((($smallest === null) && ($binding->width !== null)) ||
				(($smallest !== null) &&
					($binding->width < $smallest->width))) {
				$smallest = $binding;
			}
		}

		return $smallest;
	}

	// }}}
	// {{{ protected function getEncodingsOrderBy()

	protected function getEncodingsOrderBy()
	{
		// Load encodings by size, but put nulls first since those would be
		// audio only encodings.
		return 'width asc nulls first';
	}

	// }}}
}

?>
