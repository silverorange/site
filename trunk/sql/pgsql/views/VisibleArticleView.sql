create or replace view VisibleArticleView as
	select id, ArticleInstanceBinding.instance
		from Article
			left outer join ArticleInstanceBinding on
				Article.id = ArticleInstanceBinding.article
	where visible = true and enabled = true;
