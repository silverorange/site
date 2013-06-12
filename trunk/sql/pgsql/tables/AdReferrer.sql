create table AdReferrer (
	id serial,
	createdate timestamp,
	http_referer varchar(255),
	ad integer not null references Ad(id) on delete set null,
	primary key (id)
);

