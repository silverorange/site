<?php

require_once 'Site/SiteMailingCampaign.php';

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMailChimpCampaign extends SiteMailingCampaign
{
	// {{{ class constants

	/**
	 * Campaign Types
	 */
	const CAMPAIGN_TYPE_REGULAR       = 'regular';
	const CAMPAIGN_TYPE_PLAINTEXT     = 'plaintext';
	const CAMPAIGN_TYPE_ABSPLIT       = 'absplit';
	const CAMPAIGN_TYPE_RSS           = 'rss';
	const CAMPAIGN_TYPE_TRANSACTIONAL = 'trans';

	// }}}
	// {{{ public properties

	public $id;
	public $type;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, $shortname)
	{
		parent::__construct($app, $shortname);
		$this->type = self::CAMPAIGN_TYPE_REGULAR;
	}

	// }}}
	// {{{ public function getResourcesArchive()

	/**
	 * Gets a base64 encoded tgz file which has all the resource files for this
	 * campaign.
	 *
	 * @return string base64 encoded tgz file
	 */
	public function getResourcesArchive()
	{
		$output = null;

		$filename = $this->getResourcesArchiveFilename();
		if ($filename !== null && file_exists($filename)) {
			$content = file_get_contents($filename);
			$output = base64_encode($content);
		}

		return $output;
	}

	// }}}
	// {{{ protected function getResourcesArchiveFilename()

	protected function getResourcesArchiveFilename()
	{
		return null;
	}

	// }}}
}

?>
