create table Media (
	id serial,

	media_set integer not null references MediaSet(id),

	title             varchar(255),
	filename          varchar(255),
	original_filename varchar(255),

	downloadable boolean not null default false,
	duration integer,
	description text,

	createdate timestamp,

	-- SiteBotrMedia specific fields
	key varchar(50),

	primary key (id)
);