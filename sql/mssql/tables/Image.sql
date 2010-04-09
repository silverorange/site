create table Image (
	id                int not null identity,

	image_set         int not null references ImageSet(id),
	title             nvarchar(255),
	filename          nvarchar(255),
	original_filename nvarchar(255),
	description       ntext,

	primary key(id)
)
