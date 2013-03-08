<?php

require_once 'Site/dataobjects/SiteVideoMedia.php';
require_once 'Site/dataobjects/SiteBotrMediaEncodingBindingWrapper.php';

/**
 * A BOTR-specific media object
 *
 * @package   Site
 * @copyright 2011-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteBotrMedia extends SiteVideoMedia
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
}

?>
