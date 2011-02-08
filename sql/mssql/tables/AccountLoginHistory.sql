create table AccountLoginHistory (
	id         int not null identity,

	account    int not null references Account(id) on delete cascade,
	login_date datetime2 not null,
	ip_address varchar(15),
	user_agent nvarchar(255),

	primary key (id)
);
