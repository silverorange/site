create table AdReferrer (
	id serial,
	createdate timestamp,
	ad integer not null references Ad(id) on delete set null,
	primary key (id)
);

