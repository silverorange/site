create table Media (
	id serial,

	path_key   varchar(30) not null,
	media_set  integer not null references MediaSet(id),
	image      integer references Image(id) on delete set null,

	title             varchar(255),
	filename          varchar(255),
	original_filename varchar(255),

	has_hls boolean not null default false,
	downloadable boolean not null default false,
	duration integer,
	description text,

	key varchar(50), -- deprecated

	createdate timestamp,

	-- SiteVideoMedia specific fields
	scrubber_image integer references Image(id) on delete set null,
	scrubber_image_count integer not null default 0,

	primary key (id)
);

create index Media_path_key_index on Media(path_key);
