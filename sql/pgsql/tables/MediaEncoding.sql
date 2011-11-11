create table MediaEncoding (
	id serial,

	media_set integer not null references MediaSet(id) on delete cascade,
	default_type integer references MediaType(id) on delete cascade,
	key varchar(50),
	template_id integer,
	shortname varchar(255),
	title varchar(255),
	width integer,
	default_encoding boolean not null default true,

	primary key(id)
);

CREATE INDEX MediaEncoding_shortname_index ON MediaEncoding(shortname);
