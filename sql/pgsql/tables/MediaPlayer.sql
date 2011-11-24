create table MediaPlayer (
	id serial,

	media_set integer not null references MediaSet(id) on delete cascade,
	shortname varchar(255),
	key varchar(50) not null,
	width int,
	height int,

	primary key (id)
);

create index MediaPlayer_shortname_index on MediaPlayer(shortname);
