create table AccountLoginHistory (
	id         serial,

	account    integer not null references Account(id) on delete cascade,
	login_date timestamp not null,
	ip_address varchar(15),
	user_agent varchar(255),

	primary key (id)
);
