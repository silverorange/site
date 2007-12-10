<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatMessage.php';
require_once 'Site/dataobjects/SiteAd.php';

/**
 * Edit page for Ads
 *
 * @package   Site
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAdEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Ad/edit.xml';

	/**
	 * @var SiteAd
	 */
	protected $ad;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initAd();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ protected function initAd()

	protected function initAd()
	{
		$class_name = SwatDBClassMap::get('SiteAd');
		$this->ad = new $class_name();
		$this->ad->setDatabase($this->app->db);

		if ($this->id !== null) {
			throw new AdminNotFoundException(
				Site::_('Editing ads is not allowed.'));
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null && $shortname === null) {
			$shortname = $this->generateShortname(
				$this->ui->getWidget('title')->value, $this->ad->id);

			$this->ui->getWidget('shortname')->value = $shortname;
		} elseif (!$this->validateShortname($shortname, $this->ad->id)) {
			$message = new SwatMessage(
				Site::_('Ad %s already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$sql = 'select shortname from Ad
			where shortname = %s and id %s %s';

		$sql = sprintf($sql,
			$this->app->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($this->ad->id, true),
			$this->app->db->quote($this->ad->id, 'integer'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		if ($this->id !== null) {
			throw new AdminNotFoundException(
				Site::_('Editing ads is not allowed.'));
		}

		$this->ad->createdate = new SwatDate();
		$this->ad->createdate->toUTC();
		$this->saveAd();

		$message = new SwatMessage(
			sprintf(Site::_('“%s” has been saved.'), $this->ad->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function saveAd()

	protected function saveAd()
	{
		$values = $this->ui->getValues(array('title', 'shortname'));

		$this->ad->title = $values['title'];
		$this->ad->shortname = $values['shortname'];
		$this->ad->save();
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		// ads cannot be edited
	}

	// }}}
}

?>
