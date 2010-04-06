create table MailingListMemberUpdaterCache (
	id serial,
	email varchar(255) not null,
	rating int,
	field varchar(255),
	value varchar(500),
	primary key (id)
);

create index MailingListMemberUpdaterCache_email_index on MailingListMemberUpdaterCache(email);
create index MailingListMemberUpdaterCache_field_index on MailingListMemberUpdaterCache(field);
