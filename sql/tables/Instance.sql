create table Instance (
	id serial,
	shortname varchar(255),
	primary key (id)
);

CREATE INDEX Instance_shortname_index ON Instance(shortname);


