<?php

require_once 'Site/SitePath.php';

/**
 * @package   Site
 * @copyright 2007 silverorange
 */
class SiteArticlePath extends SitePath
{
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
