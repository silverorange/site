<?php

require_once 'Site/SiteGadget.php';
require_once 'Swat/SwatString.php';

/**
 * Displays arbitrary content
 *
 * Available settings are:
 *
 * - <code>text    content</code>      - the content to display.
 * - <code>boolean allow_markup</code> - whether or not to allow XHTML markup
 *                                       to be rendered in content. If true,
 *                                       no escaping will be done on the
 *                                       content. Othewise, special XML
 *                                       characters in the content are escaped.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContentGadget extends SiteGadget
{
	// {{{ protected function displayTitle()

	public function displayTitle()
	{
		if ($this->hasValue('title')) {
			parent::displayTitle();
		}
	}

	// }}}
	// {{{ public function displayContent()

	public function displayContent()
	{
		$content = $this->getValue('content');

		if (!$this->getValue('allow_markup')) {
			$content = SwatString::minimizeEntities($content);
		}

		echo $content;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Site::_('Arbitrary Content'));
		$this->defineSetting('content', Site::_('Content'), 'text');
		$this->defineSetting('allow_markup', Site::_('Allow Markup'),
			'boolean', false);

		$this->defineDescription(Site::_(
			'Provides a place to place arbitrary content in the sidebar. '.
			'Content may include custom XHTML by specifying the '.
			'“allow_markup” setting.'));
	}

	// }}}
}

?>
