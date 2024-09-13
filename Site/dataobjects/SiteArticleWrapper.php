<?php

/**
 * A recordset wrapper class for SiteArticle objects
 *
 * @package   Site
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteArticle
 */
class SiteArticleWrapper extends SwatDBRecordsetWrapper
{


	/**
	 * Gets a single article from this recordset by the article's shortname
	 *
	 * If two or more articles in the recordset have the same shortname, the
	 * first one is returned.
	 *
	 * @param string $shortname the shortname of the article to get from this
	 *                           recordset.
	 */
	public function getByShortname($shortname)
	{
		$returned_article = null;

		foreach($this as $article) {
			if ($article->shortname === $shortname) {
				$returned_article = $article;
				break;
			}
		}

		return $returned_article;
	}




	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get(SiteArticle::class);
		$this->index_field = 'id';
	}


}

?>
