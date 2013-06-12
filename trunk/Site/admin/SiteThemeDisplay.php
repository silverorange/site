<?php

require_once 'Swat/SwatImageDisplay.php';
require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SiteTheme.php';

/**
 * Displays a theme with a button to select the theme
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteThemeDisplay extends SwatControl
{
	// {{{ public properties

	/**
	 * @var boolean
	 */
	public $selected = false;

	// }}}
	// {{{ protected properties

	/**
	 * @var SiteTheme
	 */
	protected $theme;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/site/admin/styles/site-theme-display.css',
			Site::PACKAGE_ID);
	}

	// }}}
	// {{{ public function setTheme()

	public function setTheme(SiteTheme $theme)
	{
		$this->theme = $theme;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if (!($this->theme instanceof SiteTheme))
			return;

		parent::display();

		$button = $this->getCompositeWidget('button');
		$button->sensitive = $this->isSensitive();

		$container_div = new SwatHtmlTag('div');
		$container_div->class = $this->getCSSClassString();
		$container_div->id = $this->id;
		$container_div->open();

		if ($this->theme->fileExists('thumbnail.png')) {
			if ($this->theme->fileExists('screenshot.png')) {
				$has_screenshot = true;
				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = sprintf('Theme/ImageLoader?screenshot=%s',
					$this->theme->getShortname());

				$anchor_tag->title = Site::_('View Screenshot');
				$anchor_tag->open();
			} else {
				$has_screenshot = false;
			}

			$image = $this->getCompositeWidget('image');
			$image->alt = sprintf(Site::_('Thumbnail image for %s'),
				$this->theme->getTitle());

			$image->width = 150;
			$image->height = 100;

			$image->image = sprintf('Theme/ImageLoader?thumbnail=%s',
				$this->theme->getShortname());

			$image->display();

			if ($has_screenshot) {
				$anchor_tag->close();
			}
		}

		if (!$this->selected) {
			$controls_div = new SwatHtmlTag('div');
			$controls_div->class = 'site-theme-display-controls';
			$controls_div->open();
			$button->display();
			$controls_div->close();
		}

		$content_div = new SwatHtmlTag('div');
		$content_div->class = 'site-theme-display-content';
		$content_div->open();

		$header_tag = new SwatHtmlTag('h3');
		$header_tag->class = 'site-theme-display-title';
		$header_tag->setContent($this->theme->getTitle());
		$header_tag->display();

		$description_div = new SwatHtmlTag('div');
		$description_div->class = 'site-theme-display-description';
		$description_div->setContent($this->getDescription(), 'text/xml');
		$description_div->display();

		$author_div = new SwatHtmlTag('div');
		$author_div->class = 'site-theme-display-author';
		$author_div->open();

		echo SwatString::minimizeEntities($this->theme->getAuthor());

		if ($this->theme->getEmail() !== null) {
			echo ' - ';
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = sprintf('mailto:%s', $this->theme->getEmail());
			$a_tag->setContent($this->theme->getEmail());
			$a_tag->display();
		}

		$author_div->close();

		$license_div = new SwatHtmlTag('div');
		$license_div->class = 'site-theme-display-license';

		$content_div->close();
		$container_div->close();
	}

	// }}}
	// {{{ public function hasBeenClicked()

	/**
	 * Returns whether this theme display has been clicked
	 *
	 * @return boolean whether this theme display has been clicked.
	 */
	public function hasBeenClicked()
	{
		$button = $this->getCompositeWidget('button');
		return $button->hasBeenClicked();
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this theme display
	 *
	 * @return array the array of CSS classes that are applied to this theme
	 *                display.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('site-theme-display');

		if ($this->selected) {
			$classes[] = 'site-theme-display-selected';
		}

		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
	// {{{ protected function getDescription()

	protected function getDescription()
	{
		$description = trim($this->theme->getDescription());
		$description = SwatString::minimizeEntities($description);
		$description = SwatString::linkify($description);

		// normalize whitespace
		$description = str_replace("\r\n", "\n", $description);
		$description = str_replace("\r", "\n", $description);

		// convert double line breaks to paragraphs
		$description = preg_replace('/[\xa0\s]*\n[\xa0\s]*\n[\xa0\s]*/su',
			'</p><p>', $description);

		$description = '<p>'.$description.'</p>';

		return $description;
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$button = new SwatButton();
		$button->id = $this->id.'_button';
		$button->title = Site::_('Select Theme');
		$button->classes[] = 'site-theme-display-button';
		$this->addCompositeWidget($button, 'button');

		$image = new SwatImageDisplay();
		$image->id = $this->id.'_image';
		$image->classes[] = 'site-theme-display-image';
		$this->addCompositeWidget($image, 'image');
	}

	// }}}
}

?>
