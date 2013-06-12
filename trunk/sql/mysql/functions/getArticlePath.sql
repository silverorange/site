/*
 * Returns the path string of an article.
 *
 * @param_parent INTEGER: the id of the category to search from.
 *
 * Returns a VARCHAR containing the path string for the given category. If the
 * category does not exist, NULL is returned.
 */

CREATE FUNCTION getArticlePath(param_id INTEGER) RETURNS VARCHAR(255)
	BEGIN
		DECLARE local_parent INTEGER;
		DECLARE local_shortname VARCHAR(255);
		DECLARE local_path VARCHAR(255);

		SET local_path = NULL;

		-- get current article results
		SELECT parent, shortname INTO local_parent, local_shortname
		FROM Article
		WHERE id = param_id;

		IF FOUND THEN
			SET local_path = local_shortname;
		END IF;

		-- get parent article results
		WHILE local_parent IS NOT NULL DO

			SELECT parent, shortname INTO local_parent, local_shortname
			FROM Article
			WHERE id = local_parent;

			IF FOUND THEN
				IF local_path IS NULL THEN
					SET local_path = local_shortname;
				ELSE
					SET local_path = CONCAT(local_shortname, '/', local_path);
				END IF;
			END IF;
		END WHILE;

		RETURN local_path;
	END;
