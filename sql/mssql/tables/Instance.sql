create table Instance (
	id        int not null identity,

	shortname varchar(255) null,

	primary key (id)
);

CREATE INDEX Instance_shortname_index ON Instance(shortname);
