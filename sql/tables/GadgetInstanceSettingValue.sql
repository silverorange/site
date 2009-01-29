create table GadgetInstanceSettingValue (
	id serial,
	gadget_instance integer not null
		references GadgetInstance(id) on delete cascade,

	name varchar(255) not null,

	value_boolean boolean,
	value_date    timestamp,
	value_float   double precision,
	value_integer integer,
	value_string  varchar(255),
	value_text    text,

	primary key (id)
);
