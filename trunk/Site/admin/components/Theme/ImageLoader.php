<?php

require_once 'Site/layouts/SiteLayout.php';
require_once 'Admin/pages/AdminPage.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';

/**
 * Theme thumbnail image loader
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteThemeImageLoader extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $theme;

	/**
	 * @var string
	 */
	protected $type;

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Site/layouts/xhtml/fileloader.php');
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$thumbnail = SiteApplication::initVar('thumbnail', null,
			SiteApplication::VAR_GET);

		$screenshot = SiteApplication::initVar('screenshot', null,
			SiteApplication::VAR_GET);

		if ($screenshot !== null) {
			$this->theme = $screenshot;
			$this->type  = 'screenshot';
		} elseif ($thumbnail !== null) {
			$this->theme = $thumbnail;
			$this->type  = 'thumbnail';
		}

		if ($this->theme == '') {
			throw new AdminNotFoundException('No theme specified.');
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$themes = $this->app->theme->getAvailable();

		if (!array_key_exists($this->theme, $themes)) {
			throw new AdminNotFoundException(sprintf(
				'Theme image not found: ‘%s’.', $this->theme));
		}

		$theme = $themes[$this->theme];
		$filename = $this->type.'.png';

		if (!$theme->fileExists($filename)) {
			throw new AdminNotFoundException(sprintf(
				'Theme %s not found: ‘%s’.', $this->type, $this->theme));
		}

		header('Content-Type: image/png');

		readfile($theme->getPath().'/'.$filename, true);

		ob_flush();

		exit();
	}

	// }}}
}

?>
