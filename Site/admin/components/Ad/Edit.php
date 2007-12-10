<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatMessage.php';
require_once 'Store/dataobjects/StoreAd.php';

/**
 * Edit page for Ads
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAdEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $fields;
	protected $ad;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initAd();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML(dirname(__FILE__).'/edit.xml');

		$this->fields = array('title', 'shortname');
	}

	// }}}
	// {{{ protected function initAd()

	protected function initAd()
	{
		$class_name = SwatDBClassMap::get('StoreAd');
		$this->ad = new $class_name();
		$this->ad->setDatabase($this->app->db);

		if ($this->id !== null) {
				throw new AdminNotFoundException(
					sprintf(Admin::_('You aren\'t allowed to edit Ads.'),
						$this->id));
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
				$this->ui->getWidget('title')->value, $this->id);
			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname, $this->id)) {
			$message = new SwatMessage(
				Store::_('Short name already exists and must be unique.'),
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
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		if ($this->id === null) {
			$date = new SwatDate();
			$date->toUTC();
			$this->ad->createdate = $date->getDate();
			$this->saveAd();

			// create ad locale bindings
			$sql = sprintf('insert into AdLocaleBinding (ad, locale)
				select %s, Locale.id as locale from Locale',
				$this->app->db->quote($this->ad->id, 'integer'));

			SwatDB::exec($this->app->db, $sql);
		} else {
			$this->saveAd();
		}

		$message = new SwatMessage(
			sprintf(Store::_('“%s” has been saved.'), $this->ad->title));

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
	}

	// }}}
}

?>
