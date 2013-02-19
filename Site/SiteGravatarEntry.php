<?php

require_once 'Swat/SwatEmailEntry.php';
require_once 'Swat/SwatYUI.php';

/**
 * @package   Site
 * @copyright 2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteGravatarEntry extends SwatEmailEntry
{
	// {{{ public properties

	/**
	 * Preview image width
	 *
	 * @var integer
	 */
	public $image_width = 64;

	/**
	 * Preview image height
	 *
	 * @var integer
	 */
	public $image_height = 64;

	/**
	 * Fallback image if no Gravatar exists for the email address
	 *
	 * @var string
	 */
	public $default_image = '';

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$yui = new SwatYUI(array('dom', 'event'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript('packages/site/javascript/site-gravatar-entry.js');

		$this->requires_id = true;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		$container = new SwatHtmlTag('div');
		$container->id = $this->id.'_container';
		$container->class = 'gravatar-edit-container';
		$container->open();

		echo '<div class="gravatar-entry-content">';
		parent::display();
		$this->displayEditLink();
		echo '</div>';

		$preview = new SwatHtmlTag('div');
		$preview->id = $this->id.'_preview';
		$preview->class = 'gravatar-entry-image avatar';
		$preview->style = sprintf(
			'width: %spx; height: %spx',
			(integer)$this->image_width,
			(integer)$this->image_height
		);
		$preview->alt = 'avatar';
		$preview->setContent('');
		$preview->display();

		$container->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function displayEditLink()

	protected function displayEditLink()
	{
		$link = new SwatHtmlTag('a');
		$link->id = $this->id.'_link';
		$link->href = 'http://www.gravatar.com/';
		if ($this->value != '') {
			$link->href.= md5(trim($this->value));
		}
		$link->target = '_blank';
		$link->setContent('Gravatar.com');

		$div = new SwatHtmlTag('div');
		$div->class = 'gravatar-edit-link';
		$div->setContent(
			sprintf(
				Site::_('Change your avatar on %s'),
				$link
			),
			'text/xml'
		);
		$div->display();
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$size = round(($this->image_width + $this->image_height) / 2);

		return sprintf(
			"var %s_obj = new SiteGravatarEntry(%s, %s, %s);\n",
			$this->id,
			SwatString::quoteJavaScriptString($this->id),
			(integer)$size,
			SwatString::quoteJavaScriptString($this->default_image)
		);
	}

	// }}}
}

?>
