create table Ad (
	id serial,
	shortname varchar(255),
	title varchar(255),
	createdate timestamp,
	displayorder int not null default 0,
	total_referrers int not null default 0,
	primary key (id)
);
