create table MailingListMemberCache (
	id serial,
	email varchar(255) not null,
	primary key (id)
);

create index MailingListMemberCache_email_index on MailingListMemberCache(email);
