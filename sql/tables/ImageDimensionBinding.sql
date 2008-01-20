create table ImageDimensionBinding (
	image integer not null references Image(id),
	dimension integer not null references ImageDimension(id),
	image_type integer not null references ImageType(id),
	width integer not null,
	height integer not null,
	primary key(image, dimension)
);
