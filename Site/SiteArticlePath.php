<?php

require_once 'Site/SitePath.php';

/**
 * @package   Site
 * @copyright 2007 silverorange
 */
class SiteArticlePath extends SitePath
{
	// {{{ public function __construct()

	/**
	 * Creates a new article path object
	 *
	 * @param SiteWebApplication $app the application this path exists in.
	 * @param integer $id the database id of the object to create the path for.
	 *                     If no database id is specified, an empty path is
	 *                     created.
	 */
	public function __construct(SiteWebApplication $app, $id = null)
	{
		if ($id !== null)
			$this->loadFromId($app, $id);
	}

	// }}}
	// {{{ protected function loadFromId()

	/**
	 * Creates a new path object
	 *
	 * @param integer $article_id.
	 */
	public function loadFromId(SiteWebApplication $app, $article_id)
	{
		foreach ($this->queryPath($app, $article_id) as $row)
			$this->addEntry(new SitePathEntry(
				$row->id, $row->parent, $row->shortname, $row->title));
	}

	// }}}
	// {{{ protected function queryPath()

	protected function queryPath($app, $article_id)
	{
		$sql = sprintf('select * from getArticlePathInfo(%s)',
			$app->db->quote($article_id, 'integer'));

		return SwatDB::query($app->db, $sql);
	}

	// }}}
}

?>
