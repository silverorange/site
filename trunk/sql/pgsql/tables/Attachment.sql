create table Attachment (
	id serial,

	attachment_set integer not null
		references AttachmentSet(id) on delete cascade,

	title varchar(255) not null,

	obfuscated_id varchar(255),
	human_filename varchar(255),
	original_filename varchar(255),

	createdate timestamp,
	-- store file size as a decimal to support large file sizes as we can't
	-- support bigints. length of field assumes attachments won't be larger than
	-- 5GB.
	file_size decimal(10, 0) not null default 0,
	mime_type varchar(255) not null,
	on_cdn boolean not null default false,

	primary key (id)
);
