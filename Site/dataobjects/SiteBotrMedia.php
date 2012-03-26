<?php

require_once 'Site/dataobjects/SiteMedia.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingBindingWrapper.php';

/**
 * A BOTR-specific media object
 *
 * @package   Site
 * @copyright 2011-2012 silverorange
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
	// {{{ public function encodingExistsByKey()

	public function encodingExistsByKey($key)
	{
		$binding = $this->getEncodingBindingByKey($key);

		return ($binding instanceof SiteMediaEncodingBinding);
	}

	// }}}
	// {{{ public function getEncodingBindingByKey()

	public function getEncodingBindingByKey($key)
	{
		$encoding = $this->media_set->getEncodingByKey($key);

		foreach ($this->encoding_bindings as $binding) {
			$id = ($binding->media_encoding instanceof SiteBotrMediaEncoding) ?
				$binding->media_encoding->id : $binding->media_encoding;

			if ($encoding->id === $id) {
				return $binding;
			}
		}

		return null;
	}

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
	// {{{ public function getDefaultAudioEncoding()

	public function getDefaultAudioEncoding()
	{
		$audio = null;

		foreach ($this->encoding_bindings as $binding) {
			// Return first encoding that has an audio mime type. This can be
			// improved in the future.
			if (strpos($binding->media_type->mime_type, 'audio') !== false) {
				$audio = $binding;
				break;
			}
		}

		return $audio;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('media_set',
			SwatDBClassMap::get('SiteBotrMediaSet'));
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingWrapperClass()

	protected function getMediaEncodingBindingWrapperClass()
	{
		return SwatDBClassMap::get('SiteBotrMediaEncodingBindingWrapper');
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingsOrderBy()

	protected function getMediaEncodingBindingsOrderBy()
	{
		// Load encodings by size, but put nulls first since those would be
		// audio only encodings.
		return 'order by width asc nulls first';
	}

	// }}}
}

?>
