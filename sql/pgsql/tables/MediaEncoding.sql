create table MediaEncoding (
	id serial,

	media_set    integer not null references MediaSet(id) on delete cascade,
	default_type integer references MediaType(id) on delete set null,

	title     varchar(255),
	shortname varchar(255),

	default_encoding boolean not null default true,

	-- SiteBotrMedia Specific Fields
	key varchar(50),
	width integer,

	primary key(id)
);

create index MediaEncoding_shortname_index on MediaEncoding(shortname);
