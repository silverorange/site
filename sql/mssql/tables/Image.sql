create table Image (
	id                int not null identity,

	image_set         int not null references ImageSet(id),
	title             nvarchar(255) null,
	filename          nvarchar(255) null,
	original_filename nvarchar(255) null,
	description       ntext null,

	primary key(id)
);
