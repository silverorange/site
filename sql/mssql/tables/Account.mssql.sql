create table Account (
	id            int not null identity,

	instance      int references Instance(id),
	fullname      nvarchar(255),
	email         nvarchar(255),
	password      nvarchar(255),
	password_salt varchar(50),
	password_tag  varchar(255),
	createdate    datetimeoffset(0),
	last_login    datetimeoffset(0),

	primary key (id)
)

CREATE INDEX Account_instance_index ON Account(instance)
CREATE INDEX Account_email_index ON Account(email)
