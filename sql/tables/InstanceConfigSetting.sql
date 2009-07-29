create table NewInstanceConfigSetting (
	name varchar(255) not null,
	value varchar(1024),
	is_default boolean not null default false,
	instance integer not null references Instance(id) on delete cascade,
	primary key (name, is_default, instance)
);

CREATE INDEX NewInstanceConfigSetting_name_index ON NewInstanceConfigSetting(name);
CREATE INDEX NewInstanceConfigSetting_is_default_index ON NewInstanceConfigSetting(is_default);
CREATE INDEX NewInstanceConfigSetting_instance_index ON NewInstanceConfigSetting(instance);
