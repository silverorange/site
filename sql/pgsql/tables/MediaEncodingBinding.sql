create table MediaEncodingBinding (
	media          integer not null references Media(id) on delete cascade,
	media_encoding integer not null references MediaEncoding(id),
	media_type     integer not null references MediaType(id),

	filesize integer,

	on_cdn boolean not null default false,

	primary key(media, media_encoding)
);

create index MediaEncodingBinding_media_index on MediaEncodingBinding(media);
