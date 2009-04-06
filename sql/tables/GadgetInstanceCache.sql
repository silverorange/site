create table GadgetInstanceCache (
	id serial,
	gadget_instance integer not null unique
		references GadgetInstance(id) on delete cascade,
	value text,
	last_update timestamp not null default LOCALTIMESTAMP,
	primary key (id)
);

create index GadgetInstanceCache_gadget_instance_index on
	GadgetInstanceCache(gadget_instance);
