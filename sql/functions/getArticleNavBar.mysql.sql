/*
 * Returns navbar information from an article.
 *
 * @param_parent INTEGER: the id of the article to search from.
 *
 * Returns a set of returned_rows ordered correctly to go in the navbar.
 * Checking if the parent articles are enabled is left up to sp_article_find.
 * If the article is not found, returns an empty recordset.
 *
 * This procedure uses recursion to output entries in the correct order for
 * applications.
 */

CREATE PROCEDURE getArticleNavBar(param_id INTEGER)
	BEGIN
		CREATE TEMPORARY TABLE Tree_Ids(displayorder SERIAL, id INTEGER);
		CALL getTreeIds(param_id);

		SELECT Article.id, Article.parent, Article.shortname, Article.title
		FROM Article
			INNER JOIN Tree_Ids ON Article.id = Tree_Ids.id
		ORDER BY Tree_Ids.displayorder desc;
		
		DROP TABLE Tree_Ids;
	END;
