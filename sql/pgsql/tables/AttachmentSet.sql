create table AttachmentSet (
	id serial,

	title     varchar(255) not null,
	shortname varchar(255) not null,

	use_cdn            boolean not null default false,
	obfuscate_filename boolean not null default false,

	primary key(id)
);
