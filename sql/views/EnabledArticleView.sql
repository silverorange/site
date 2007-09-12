create or replace view EnabledArticleView as
	select id from Article
	where enabled = 't';
