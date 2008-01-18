create table ImageSet (
	id serial,
	shortname varchar(255),
	obfuscate_filename boolean not null default false,
	primary key(id)
);
