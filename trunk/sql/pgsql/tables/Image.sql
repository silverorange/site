create table Image (
	id serial,
	image_set integer not null references ImageSet(id),
	title varchar(255),
	filename varchar(255),
	original_filename varchar(255),
	description text,
	primary key(id)
);
