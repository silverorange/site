create table MediaHLSEncoding (
	id serial,
	media_set integer not null references MediaSet(id) on delete cascade,
	shortname varchar(255),
	primary key(id)
);

create index MediaHLSEncoding_shortname_index on MediaHLSEncoding(shortname);
