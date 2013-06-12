CREATE FUNCTION convertToUTC (param_date timestamp, param_time_zone varchar(50)) RETURNS timestamp
	RETURN CONVERT_TZ(param_date, param_time_zone, 'UTC');
