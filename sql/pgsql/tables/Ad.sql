create table Ad (
	id serial,
	shortname varchar(255),
	title varchar(255),
	createdate timestamp,
	displayorder int not null default 0,
	emails_sent int not null default 0,
	total_referrers int not null default 0,
	primary key (id)
);

CREATE INDEX Ad_shortname_index ON Ad(shortname);
