create table ImageDimensionBinding (
	image      int not null references Image(id),

	dimension  int not null references ImageDimension(id),
	image_type int not null references ImageType(id),
	width      int not null,
	height     int not null,
	dpi        int not null default 72,
	filesize   int null,

	primary key(image, dimension)
);

create index ImageDimensionBinding_image_index on ImageDimensionBinding(image);
