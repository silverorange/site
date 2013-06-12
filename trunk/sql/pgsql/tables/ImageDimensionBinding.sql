create table ImageDimensionBinding (
	image      integer not null references Image(id)          on delete cascade,
	dimension  integer not null references ImageDimension(id) on delete cascade,
	image_type integer not null references ImageType(id),
	width      integer not null,
	height     integer not null,
	dpi        integer not null default 72,
	filesize   integer,
	on_cdn     boolean not null default false,
	primary key(image, dimension)
);

create index ImageDimensionBinding_image_index on ImageDimensionBinding(image);
