create table MediaCdnQueue (
	id serial,

	operation  varchar(255) not null,

	-- for copy operations, and delete operations where we're just deleting from
	-- the cdn, but not deleting from the database.
	media int default null references Media(id) on delete cascade,
	encoding int default null references MediaEncoding(id) on delete cascade,

	-- for delete operations
	file_path varchar(255),

	error_date timestamp,

	primary key(id)
);
