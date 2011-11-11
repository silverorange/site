create table Media (
	id serial,

	media_set integer not null references MediaSet(id),

	title varchar(255),
	key varchar(50) not null,
	downloadable boolean not null default false,
	duration integer,

	createdate timestamp,
	error_date timestamp default null,

	primary key (id)
);

