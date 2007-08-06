create or replace view ArticleChildCountView as
	select parent as article,
		count(id) as child_count
	from Article
	group by parent;
