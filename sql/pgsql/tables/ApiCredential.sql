create table ApiCredential (
	id serial,
	title             varchar(255) not null,
	api_key           varchar(255),
	api_shared_secret varchar(255),
	createdate        timestamp not null,

	instance integer references Instance(id) on delete set null,

	primary key (id),
	unique (api_key, instance)
);

create index ApiCredential_api_key_index on ApiCredential(api_key);
create index ApiCredential_instance_index on ApiCredential(instance);
