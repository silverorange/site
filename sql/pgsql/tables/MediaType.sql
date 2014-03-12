create table MediaType (
	id serial,

	extension varchar(10),
	mime_type varchar(50),
	alternate_mime_types varchar(200),

	primary key(id)
);

