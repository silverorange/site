create table ImageType (
	id serial,
	extension varchar(10) not null,
	mime_type varchar(50) not null,
	primary key(id)
);
