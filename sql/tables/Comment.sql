create table Comment (
	id serial,
	fullname varchar(255),
	link varchar(255),
	email varchar(255),
	bodytext text not null,
	status integer not null default 0,
	spam boolean not null default false,
	ip_address varchar(15),
	user_agent varchar(255),
	createdate timestamp not null,
	primary key (id)
);

create index Comment_spam_index on Comment(spam);
create index Comment_post_index on Comment(post);
create index Comment_status_index on Comment(status);
