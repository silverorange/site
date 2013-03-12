create table Media (
	id serial,

	media_set integer not null references MediaSet(id),
	image     integer references Image(id) on delete set null,

	title             varchar(255),
	filename          varchar(255),
	original_filename varchar(255),

	downloadable boolean not null default false,
	duration integer,
	description text,

	createdate timestamp,

	-- SiteVideoMedia specific fields
	key varchar(50),

	primary key (id)
);
