create table ApiSignOnToken (
	id serial,

	ident varchar(255) not null,
	token varchar(255) not null,

	api_credential integer not null references ApiCredential(id) on delete cascade,
	createdate timestamp not null default LOCALTIMESTAMP,

	primary key (id)
);

create index ApiSignOnToken_ident_index on ApiSignOnToken(ident);
create index ApiSignOnToken_token_index on ApiSignOnToken(token);
