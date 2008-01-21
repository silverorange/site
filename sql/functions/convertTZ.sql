CREATE OR REPLACE FUNCTION convertTZ (timestamp, varchar(50)) RETURNS timestamp AS $$
	DECLARE
		param_date ALIAS FOR $1;
		param_time_zone ALIAS FOR $2;
		local_date timestamp;
	BEGIN
		select into local_date ((param_date at time zone 'UTC') at time zone param_time_zone);
		RETURN local_date;
	END;
$$ LANGUAGE 'plpgsql';
