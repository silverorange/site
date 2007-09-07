create table Article (
	id serial,
	parent int, -- constraint added below
	title varchar(255),
	description text,
	bodytext text,
	createdate timestamp not null default LOCALTIMESTAMP,
	modified_date timestamp not null default LOCALTIMESTAMP,
	displayorder int not null default 0,
	enabled boolean not null default true,
	show boolean not null default true,
	searchable boolean not null default true,
	shortname varchar(255),
	primary key (id)
);

ALTER TABLE Article ADD CONSTRAINT Articlefk FOREIGN KEY (parent) REFERENCES Article(id) MATCH FULL on delete cascade;

CREATE INDEX Article_parent_index ON Article(parent);
CREATE INDEX Article_shortname_index ON Article(shortname);
CREATE INDEX Article_show_index ON Article(show);
CREATE INDEX Article_enabled_index ON Article(enabled);
CREATE INDEX Article_searchable_index ON Article(searchable);

