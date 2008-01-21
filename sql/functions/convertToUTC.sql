CREATE OR REPLACE FUNCTION convertToUTC (timestamp, varchar(50)) RETURNS timestamp AS $$
	DECLARE
		param_date ALIAS FOR $1;
		param_time_zone ALIAS FOR $2;
		local_date timestamp;
	BEGIN
		select into local_date ((param_date at time zone param_time_zone) at time zone 'UTC');
		RETURN local_date;
	END;
$$ LANGUAGE 'plpgsql';
