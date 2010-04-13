create table Account (
	id            int not null identity,

	instance      int null references Instance(id),
	fullname      nvarchar(255) null,
	email         nvarchar(255) null,
	password      nvarchar(255) null,
	password_salt varchar(50) null,
	password_tag  varchar(255) null,
	createdate    datetime2 null,
	last_login    datetime2 null,

	primary key (id)
);

CREATE INDEX Account_instance_index ON Account(instance);
CREATE INDEX Account_email_index ON Account(email);
