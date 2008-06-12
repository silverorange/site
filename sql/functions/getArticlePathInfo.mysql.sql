/*
 * Returns path information for an article.
 *
 * @param_id INTEGER: the id of the article.
 *
 * Returns a set of type_article_path_info. The set is ordered from the leaf to the root.
 * If the article is not found, an empty record set is returned.
 */

CREATE PROCEDURE getTreeIds(IN param_id INTEGER)
	BEGIN
		DECLARE parent_id INTEGER;
		INSERT INTO Tree_Ids (id) values (param_id);

		SELECT parent INTO parent_id FROM Article where id = param_id;

		IF parent_id IS NOT NULL THEN
			CALL getTreeIds(parent_id);
		END IF;
	END;

CREATE PROCEDURE getArticlePathInfo(param_id INTEGER)
	BEGIN
		CREATE TEMPORARY TABLE Tree_Ids (displayorder SERIAL, id INTEGER);
		CALL getTreeIds(param_id);
		
		SELECT id, parent, shortname, title FROM Article WHERE id IN (
			SELECT id FROM Tree_Ids);

		DROP TABLE Tree_Ids;
	END;
