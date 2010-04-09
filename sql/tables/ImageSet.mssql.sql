create table ImageSet (
	id                 int not null identity,

	shortname          varchar(255),
	obfuscate_filename bit not null default 0,

	primary key(id)
)

CREATE INDEX ImageSet_shortname_index ON ImageSet(shortname)
