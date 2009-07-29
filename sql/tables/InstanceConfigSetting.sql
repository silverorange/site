create table InstanceConfigSetting (
	name varchar(255) not null,
	value varchar(1024),
	is_default boolean not null default false,
	instance integer not null references Instance(id) on delete cascade,
	primary key (name, is_default, instance)
);

CREATE INDEX InstanceConfigSetting_name_index ON InstanceConfigSetting(name);
CREATE INDEX InstanceConfigSetting_is_default_index ON InstanceConfigSetting(is_default);
CREATE INDEX InstanceConfigSetting_instance_index ON InstanceConfigSetting(instance);
