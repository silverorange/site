create table AccountLoginTag (
	id serial,

	account    integer not null references Account(id) on delete cascade,
	tag        varchar(255) not null,
	login_date timestamp not null,
	ip_address varchar(15) not null,
	user_agent varchar(255),

	primary key(id)
);

create index AccountLoginTag_tag on AccountLoginTag(tag);
