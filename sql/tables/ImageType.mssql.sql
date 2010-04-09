create table ImageType (
	id        int not null identity,

	extension varchar(10),
	mime_type varchar(50),

	primary key(id)
)
