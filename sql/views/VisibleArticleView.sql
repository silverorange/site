create or replace view VisibleArticleView as
	select id from Article
	where show = true and enabled = true;
