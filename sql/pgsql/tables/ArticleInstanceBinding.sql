create table ArticleInstanceBinding (
	article int not null references Article(id) on delete cascade,
	instance int not null references Instance(id) on delete cascade,
	primary key (article, instance)
);

CREATE INDEX ArticleInstanceBinding_article_index ON ArticleInstanceBinding(article);
