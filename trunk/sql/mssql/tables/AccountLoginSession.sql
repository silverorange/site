create table AccountLoginSession (
	id int not null identity,

	account    int not null references Account(id) on delete cascade,
	tag        varchar(255),
	session_id varchar(255) not null,
	createdate datetime2 not null,
	login_date datetime2 not null,
	ip_address varchar(15) not null,
	user_agent nvarchar(255),
	dirty      bit not null default 0,

	primary key(id)
);

create index AccountLoginSession_tag on AccountLoginSession(tag);
