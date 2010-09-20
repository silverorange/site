create table Image (
	id                int not null identity,

	image_set         int not null references ImageSet(id),
	title             nvarchar(255) null,
	filename          nvarchar(255) null,
	original_filename nvarchar(255) null,
	description       ntext null,
	on_cdn            bit not null default 0,

	primary key(id)
);
