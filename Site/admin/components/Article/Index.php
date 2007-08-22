<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';

require_once 'include/SiteArticleActionsProcessor.php';
require_once 'include/SiteArticleVisibilityCellRenderer.php';

/**
 * Index page for Articles
 *
 * @package   Site
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteArticleIndex extends AdminIndex 
{
	// {{{ protected properties

	protected $id = null;
	protected $parent = null;

	/**
	 * @var string
	 */
	protected $ui_xml = 'Site/admin/components/Article/admin-article-index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->mapClassPrefixToPath('Site', 'Site');
		$this->ui->loadFromXML($this->ui_xml);
		
		$this->id = SiteApplication::initVar('id');
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$processor = new SiteArticleActionsProcessor($this);
		$processor->process($view, $actions);
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = 'select Article.id,
					Article.title, 
					Article.show,
					Article.searchable,
					ArticleChildCountView.child_count
				from Article
					left outer join ArticleChildCountView on
						ArticleChildCountView.article = Article.id
				where Article.parent %s %s
				order by %s';

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->id),
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view, 
				'Article.displayorder, Article.title', 'Article'));
		
		$rs = SwatDB::query($this->app->db, $sql);

		$view = $this->ui->getWidget('index_view');
		$view->getColumn('visibility')->getFirstRenderer()->db =
			$this->app->db;

		if (count($rs) < 2)
			$this->ui->getWidget('articles_order')->sensitive = false;

		return $rs;
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal() 
	{
		parent::buildInternal();

		$articles_frame = $this->ui->getWidget('articles_frame');

		if ($this->id != 0) {
			// show the detail frame
			$details_frame = $this->ui->getWidget('details_frame');
			$details_frame->visible = true;
			
			// move the articles frame inside of the detail frame
			$articles_frame->parent->remove($articles_frame);
			$details_frame->add($articles_frame);
			$articles_frame->title = Site::_('Sub-Articles');
			$this->ui->getWidget('articles_new')->title =
				Site::_('New Sub-Article');

			$this->buildDetails();
		}

		$tool_value = ($this->id === null) ? '' : '?parent='.$this->id;	
		$this->ui->getWidget('articles_toolbar')->setToolLinkValues(
			$tool_value);

		$this->ui->getWidget('visibility')->addOptionsByArray(
			SiteArticleActionsProcessor::getActions());
	}

	// }}}
	// {{{ protected function buildDetails()

	protected function buildDetails() 
	{
		$details_block = $this->ui->getWidget('details_block');
		$details_view = $this->ui->getWidget('details_view');
		$details_frame = $this->ui->getWidget('details_frame');

		// set default time zone
		$createdate_field = $details_view->getField('createdate');
		$createdate_renderer = $createdate_field->getFirstRenderer();
		$createdate_renderer->display_time_zone =
			$this->app->default_time_zone;

		$modified_date_field = $details_view->getField('modified_date');
		$modified_date_renderer =
			$modified_date_field->getRendererByPosition();

		$modified_date_renderer->display_time_zone =
			$this->app->default_time_zone;

		$details_view->getField('visibility')->getFirstRenderer()->db =
			$this->app->db;

		$fields = array('id', 'title', 'shortname', 'description', 'bodytext',
			'show', 'parent', 'createdate', 'modified_date', 'searchable');

		$row = SwatDB::queryRowFromTable($this->app->db, 'Article', $fields, 
			'id' , $this->id);

		if ($row === null)
			throw new AdminNotFoundException(
				sprintf(Site::_('Article with id ‘%s’ not found.'),
					$this->id));

		if ($row->bodytext !== null)
			$row->bodytext = SwatString::condense(SwatString::toXHTML(
				$row->bodytext));

		if ($row->description !== null)
			$row->description = SwatString::condense(SwatString::toXHTML(
				$row->description));

		$details_frame->title = Site::_('Article');
		$details_frame->subtitle = $row->title;
		$details_view->data = &$row;

		// set link id
		$this->ui->getWidget('details_toolbar')->setToolLinkValues($this->id);

		// build navbar
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry(Site::_('Articles'),
			'Article'));

		if ($row->parent != null) {
			$navbar_rs = SwatDB::executeStoredProc($this->app->db, 
				'getArticleNavBar', array($row->parent));

			foreach ($navbar_rs as $elem)
				$this->navbar->addEntry(new SwatNavBarEntry($elem->title,
					'Article/Index?id='.$elem->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry($row->title));
	}

	// }}}
}

?>
