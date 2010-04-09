create or replace view VisibleArticleView as
	select id from Article
	where visible = true and enabled = true;
