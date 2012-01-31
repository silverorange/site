create table AttachmentCdnQueue (
	id serial,

	operation varchar(255),
	attachment integer references Attachment(id) on delete cascade,
	override_http_headers text,

	file_path varchar(255),
	error_date timestamp,

	primary key (id)
);
