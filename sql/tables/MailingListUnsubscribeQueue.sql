create table MailingListUnsubscribeQueue (
	id serial,
	email varchar(255) not null,
	primary key (id)
);

create index MailingListUnsubscribeQueue_email_index on MailingListUnsubscribeQueue(email);
