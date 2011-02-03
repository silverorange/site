create or replace view EnabledArticleView as
	select id, ArticleInstanceBinding.instance
		from Article
			left outer join ArticleInstanceBinding on
				Article.id = ArticleInstanceBinding.article
	where enabled = true;
