/*
 * Returns the path string of an article.
 *
 * @param_parent INTEGER: the id of the category to search from.
 *
 * Returns a VARCHAR containing the path string for the given category. If the
 * category does not exist, NULL is returned.
 */
CREATE OR REPLACE FUNCTION getArticlePath(INTEGER) RETURNS VARCHAR(255) AS $$
	DECLARE
		param_id ALIAS FOR $1;
		local_parent INTEGER;
		local_shortname VARCHAR(255);
		local_path VARCHAR(255);
	BEGIN
		local_path = NULL; 

		-- get current article results
		SELECT INTO local_parent, local_shortname parent, shortname
		FROM Article 
		WHERE id = param_id;

		IF FOUND THEN
			local_path = local_shortname;
		END IF;

		-- get parent article results
		WHILE local_parent IS NOT NULL LOOP
			BEGIN

				SELECT INTO local_parent, local_shortname parent, shortname
				FROM Article 
				WHERE id = local_parent;

				IF FOUND THEN
					IF local_path IS NULL THEN
						local_path = local_shortname;
					ELSE
						local_path = local_shortname || '/' || local_path;
					END IF;
				END IF;

			END;
		END LOOP;

		RETURN local_path;
	END;
$$ LANGUAGE 'plpgsql';
