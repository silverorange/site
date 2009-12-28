create table MailingListSegmenterCache (
	id serial,
	email varchar(255) not null,
	rating int,
	segment char(1),
	primary key (id)
);

create index MailingListSegmenterCache_email_index on MailingListSegmenterCache(email);
