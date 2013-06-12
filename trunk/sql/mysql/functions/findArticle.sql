/*
 * Finds an article in the article tree.
 *
 * @param_source VARCHAR(255): a string containing the path to the article delimited by forward slashes.
 *
 * Returns an integer containing the id of the searched for article.
 * If the article is not found, returns NULL.
 */
CREATE FUNCTION findArticle (param_source VARCHAR(255)) RETURNS INTEGER
	BEGIN
		DECLARE local_source VARCHAR(255);
		DECLARE local_shortname VARCHAR(255);
		DECLARE local_parent INTEGER;
		DECLARE local_pos INTEGER;
		DECLARE local_id INTEGER;

		-- Find the first forward slash in the source string.
		SET local_source = CONCAT(param_source, '/');
		SET local_pos = POSITION('/' IN local_source);

		SET local_id = NULL;

		WHILE local_pos != 0 DO
			BEGIN
				-- Get shortname from beginning of source string.
				SET local_shortname = SUBSTRING(local_source FROM 1 FOR (local_pos - 1));
				-- Get the remainder of the source string.
				SET local_source = SUBSTRING(local_source FROM local_pos + 1 FOR character_length(local_source) - local_pos);

				-- Get the id of the parent
				SELECT id INTO local_id
					FROM Article
					WHERE (Article.parent = local_id OR (local_id is null AND parent is null))
						AND shortname = local_shortname
						AND id != 0;

				IF local_id IS NULL THEN
					RETURN NULL;
				END IF;

				-- Find next forward slash in the source string.
				SET local_pos = POSITION('/' IN local_source);
			END;
		END WHILE;

		RETURN local_id;
	END;
