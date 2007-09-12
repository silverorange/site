-- renamed from customers
create table Account (
	id serial,
	fullname varchar(255),
	email varchar(255),
	password varchar(255),
	password_salt varchar(50),
	password_tag varchar(255),
	createdate timestamp,
	last_login timestamp,
	primary key (id)
);
