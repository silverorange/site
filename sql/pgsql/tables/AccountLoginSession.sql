create table AccountLoginSession (
	id serial,

	account    integer not null references Account(id) on delete cascade,
	tag        varchar(255),
	session_id varchar(255) not null,
	createdate timestamp not null,
	login_date timestamp not null,
	ip_address varchar(15) not null,
	user_agent varchar(255),
	dirty      boolean not null default false,

	primary key(id)
);

create index AccountLoginSession_tag on AccountLoginSession(tag);
