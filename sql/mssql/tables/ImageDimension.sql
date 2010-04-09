create table ImageDimension (
	id            int not null identity,

	image_set     int not null references ImageSet(id) on delete cascade,
	default_type  int null references ImageType(id) on delete cascade,
	shortname     varchar(255) null,
	title         nvarchar(255) null,
	max_width     int null,
	max_height    int null,
	crop          bit not null default 0,
	dpi           int not null default 72,
	quality       int not null default 85,
	strip         bit not null default 1,
	interlace     bit not null default 0,
	resize_filter varchar(50) null,

	primary key(id)
);

CREATE INDEX ImageDimension_shortname_index ON ImageDimension(shortname);
