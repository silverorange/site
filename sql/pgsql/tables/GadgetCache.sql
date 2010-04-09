create table GadgetCache (
	id serial,
	gadget_instance integer not null
		references GadgetInstance(id) on delete cascade,

	name varchar(255) not null,
	value text,
	last_update timestamp not null default LOCALTIMESTAMP,
	primary key(id)
);

CREATE INDEX GadgetCache_instance_index ON GadgetCache(gadget_instance);
