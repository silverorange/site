create table ImageDimension (
	id serial,
	image_set integer not null references ImageSet(id) on delete cascade,
	default_type integer not null references ImageType(id) on delete cascade,
	shortname varchar(255),
	title varchar(255),
	max_width integer,
	max_height integer,
	crop boolean not null default false,
	dpi integer not null default 72,
	quality integer not null default 85,
	strip boolean not null default true,
	interlace boolean not null default false,
	primary key(id)
);
