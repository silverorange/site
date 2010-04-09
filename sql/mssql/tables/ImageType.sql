create table ImageType (
	id        int not null identity,

	extension varchar(10) null,
	mime_type varchar(50) null,

	primary key(id)
)
