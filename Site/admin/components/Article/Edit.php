<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDate.php';
require_once 'Site/dataobjects/SiteArticle.php';

/**
 * Edit page for Articles
 *
 * @package   Site
 * @copyright 2005-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticleEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $parent;
	protected $edit_article;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Article/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->ui_xml);

		$this->initArticle();

		$this->parent = SiteApplication::initVar('parent');

		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('parent', $this->parent);

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;
	}

	// }}}
	// {{{ protected function initArticle()

	protected function initArticle()
	{
		$class_name = SwatDBClassMap::get('SiteArticle');
		$this->edit_article = new $class_name();
		$this->edit_article->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->edit_article->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Site::_('Article with id "%s" not found.'),
						$this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname');
		$title = $this->ui->getWidget('title');

		if ($this->id === null && $shortname->value === null) {
			$new_shortname = $this->generateShortname($title->value);
			$shortname->value = $new_shortname;
		} elseif (!$this->validateShortname($shortname->value)) {
			$message = new SwatMessage(
				Site::_('Shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$shortname->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$valid = true;

		$class_name = SwatDBClassMap::get('SiteArticle');
		$article = new $class_name();
		$article->setDatabase($this->app->db);

		if ($article->loadByShortname($shortname)) {
			if ($article->id !== $this->edit_article->id &&
				$article->getInternalValue('parent') == $this->parent) {
				$valid = false;
			}
		}

		return $valid;
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$now = new SwatDate();
		$now->toUTC();

		if ($this->id === null)
			$this->edit_article->createdate = $now->getDate();

		$this->edit_article->parent        = $this->parent;
		$this->edit_article->modified_date = $now->getDate();

		$this->saveArticle();
		$this->addToSearchQueue();

		$message = new SwatMessage(
			sprintf(Site::_('“%s” has been saved.'),
				$this->edit_article->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		// this is automatically wrapped in a transaction because it is
		// called in saveDBData()
		$type = NateGoSearch::getDocumentType($this->app->db, 'article');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->edit_article->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->edit_article->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function saveArticle()

	protected function saveArticle()
	{
		$values = $this->ui->getValues(array('title', 'shortname', 'bodytext',
			'description', 'enabled', 'visible', 'searchable'));

		$this->edit_article->title         = $values['title'];
		$this->edit_article->shortname     = $values['shortname'];
		$this->edit_article->bodytext      = $values['bodytext'];
		$this->edit_article->description   = $values['description'];
		$this->edit_article->enabled       = $values['enabled'];
		$this->edit_article->visible       = $values['visible'];
		$this->edit_article->searchable    = $values['searchable'];

		$this->edit_article->save();
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->edit_article));

		$this->parent = $this->edit_article->getInternalValue('parent');
		$form = $this->ui->getWidget('edit_form');
		$form->addHiddenField('parent', $this->parent);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->id !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getArticleNavBar', array($this->id));

			foreach ($navbar_rs as $elem)
				$this->navbar->addEntry(new SwatNavBarEntry($elem->title,
					'Article/Index?id='.$elem->id));

		} elseif ($this->parent !== null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getArticleNavBar', array($this->parent));

			foreach ($navbar_rs as $elem)
				$this->navbar->addEntry(new SwatNavBarEntry($elem->title,
					'Article/Index?id='.$elem->id));
		}

		parent::buildNavBar();
	}

	// }}}
}

?>
