create table ApiCredential (
	id serial,
	title             varchar(255) not null,
	api_key           varchar(255) unique,
	api_shared_secret varchar(255),
	createdate        timestamp not null,
	primary key (id)
);

create index ApiCredential_api_key on ApiCredential(api_key);

-- TEMP
alter table Account add api_credential integer references ApiCredential(id);
CREATE INDEX Account_api_credential_index ON Account(api_credential);
