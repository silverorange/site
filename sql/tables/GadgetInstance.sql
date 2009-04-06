create table GadgetInstance (
	id serial,
	instance integer references Instance(id) on delete cascade,
	gadget varchar(255) not null,
	cache integer references GadgetInstanceCache(id) on delete set null,
	displayorder integer not null default 0,
	primary key (id)
);

create index GadgetInstance_instance_index on
	GadgetInstance(instance);
