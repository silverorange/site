insert into MediaType (
	extension,
	mime_type,
	alternate_mime_types
) values (
	'mp4',
	'video/mp4',
	null
);

insert into MediaType (
	extension,
	mime_type,
	alternate_mime_types
) values (
	'm4a',
	'audio/mp4',
	'audio/aac, audio/x-m4a, audio/MP4A-LATM, audio/mpeg4-generic'
);

insert into MediaType (
	extension,
	mime_type,
	alternate_mime_types
) values (
	'mp3',
	'audio/mpeg',
	'audio/mpeg, audio/mp3, audio/MPA, audio/mpa-robust'
);
