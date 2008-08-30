create table InstanceConfigSetting (
	id serial,
	instance integer not null references Instance(id) on delete cascade,
	name varchar(255) not null,
	value varchar(1024),
	primary key (id)
);

CREATE INDEX InstanceConfigSetting_name_index ON InstanceConfigSetting(name);
