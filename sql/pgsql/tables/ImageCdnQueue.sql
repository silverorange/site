create table ImageCdnQueue (
	id serial,

	operation  varchar(255) not null,
	image_path varchar(255) not null,

	primary key(id)
);
