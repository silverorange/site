create table GadgetInstance (
	id serial,
	instance integer references Instance(id) on delete cascade,
	gadget varchar(255) not null,
	cache_value text,
	cache_last_update timestamp,
	displayorder integer not null default 0,
	primary key (id)
);

create index GadgetInstance_instance_index on
	GadgetInstance(instance);
