create table Account (
	id serial,
	instance integer references Instance(id),
	fullname varchar(255),
	email varchar(255),
	password varchar(255),
	password_salt varchar(50),
	password_tag varchar(255),
	createdate timestamp,
	last_login timestamp,
	delete_date timestamp,
	primary key (id)
);

CREATE INDEX Account_instance_index ON Account(instance);
CREATE INDEX Account_email_index ON Account(email);
