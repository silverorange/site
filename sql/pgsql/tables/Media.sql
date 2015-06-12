create table Media (
	id serial,

	key varchar(50),
	media_set integer not null references MediaSet(id),
	image     integer references Image(id) on delete set null,

	title             varchar(255),
	filename          varchar(255),
	original_filename varchar(255),

	has_hls boolean not null default false,
	downloadable boolean not null default false,
	duration integer,
	description text,

	createdate timestamp,

	-- SiteVideoMedia specific fields
	scrubber_image integer references Image(id) on delete set null,
	scrubber_image_count integer not null default 0,

	primary key (id)
);
