create table AdReferrer (
	id serial,
	createdate timestamp,
	ad int not null references Ad(id),
	primary key (id)
);

