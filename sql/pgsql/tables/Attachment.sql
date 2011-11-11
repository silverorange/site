create table Attachment (
	id serial,

	attachment_set integer not null references AttachmentSet(id) on delete cascade,
	title varchar(255) not null,
	original_filename varchar(255),
	createdate timestamp,
	-- assume attachments won't be larger than 5GB
	file_size decimal(10, 0) not null default 0,
	mime_type varchar(50),
	on_cdn boolean not null default false,

	primary key (id)
);
