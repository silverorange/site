create table MediaSet (
	id serial,

	instance integer not null references Instance(id) on delete cascade,
	shortname varchar(255),

	primary key (id)
);

create index MediaSet_instance_index on MediaSet(instance);
create index MediaSet_shortname_index on MediaSet(shortname);
