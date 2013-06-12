create table SiteEmailLog (
	id serial,
	createdate timestamp,
	instance integer default null,
	type varchar(255),
	attachment_count integer default 0,
	attachment_size integer default 0,
	to_address varchar(255),
	from_address varchar(255),
	recipient_type varchar(3),

	primary key (id)
);

