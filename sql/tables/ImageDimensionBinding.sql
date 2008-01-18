create table ImageDimensionBinding (
	image integer not null references Image(id),
	dimension integer not null references ImageDimension(id),
	width integer not null,
	height integer not null,
	primary key(image, dimension)
);
