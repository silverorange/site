insert into MediaEncoding (
	media_set,
	default_type,
	title,
	shortname,
	default_encoding,
	width
) values (
	(select id from MediaSet where shortname = 'public'),
	(select id from MediaType where mime_type = 'video/mp4'),
	'1920-wide (1080p)',
	'1920',
	true,
	1920
);

insert into MediaEncoding (
	media_set,
	default_type,
	title,
	shortname,
	default_encoding,
	width
) values (
	(select id from MediaSet where shortname = 'public'),
	(select id from MediaType where mime_type = 'video/mp4'),
	'1280-wide (720p)',
	'1280',
	true,
	1280
);

insert into MediaEncoding (
	media_set,
	default_type,
	title,
	shortname,
	default_encoding,
	width
) values (
	(select id from MediaSet where shortname = 'public'),
	(select id from MediaType where mime_type = 'video/mp4'),
	'1080-wide',
	'1080',
	true,
	1080
);

insert into MediaEncoding (
	media_set,
	default_type,
	title,
	shortname,
	default_encoding,
	width
) values (
	(select id from MediaSet where shortname = 'public'),
	(select id from MediaType where mime_type = 'video/mp4'),
	'720-wide',
	'720',
	true,
	720
);

insert into MediaEncoding (
	media_set,
	default_type,
	title,
	shortname,
	default_encoding,
	width
) values (
	(select id from MediaSet where shortname = 'public'),
	(select id from MediaType where mime_type = 'video/mp4'),
	'480-wide',
	'480',
	true,
	480
);

insert into MediaEncoding (
	media_set,
	default_type,
	title,
	shortname,
	default_encoding,
	width
) values (
	(select id from MediaSet where shortname = 'public'),
	(select id from MediaType where mime_type = 'video/mp4'),
	'320-wide (QVGA)',
	'320',
	true,
	320
);
