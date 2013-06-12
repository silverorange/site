create table MediaSet (
	id serial,

	instance           integer default null references Instance(id) on delete cascade,
	shortname          varchar(255),
	use_cdn            boolean not null default false,
	obfuscate_filename boolean not null default false,
	private            boolean not null default false,

	skin               varchar(50),

	primary key (id)
);

create index MediaSet_instance_index on MediaSet(instance);
create index MediaSet_shortname_index on MediaSet(shortname);
