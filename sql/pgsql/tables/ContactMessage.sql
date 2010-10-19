create table ContactMessage as (
	id         serial,

	instance   integer references Instance(id),
	subject    varchar(255) not null,
	email      varchar(255) not null,
	message    text not null,
	spam       boolean not null default false,
	ip_address varchar(15),
	user_agent varchar(255),
	createdate timestamp not null,
	sent_date  timestamp,
	error_date timestamp,

	primary key (id)
);

create index ContactMessage_spam_index on ContactMessage(spam);
