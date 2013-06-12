<!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <html class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <html class="ie8"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> <html> <!--<![endif]-->

<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<base href="<?= $this->basehref ?>" />
	<?=$this->html_head_entries?>
	<title><?= $this->html_title ?></title>
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<meta name="description" content="<?= $this->meta_description ?>" />
	<meta name="ids" content="<?= $this->meta_keywords ?>" />
</head>

<body>
	<div id="main-content">
		<div id="body-content">
			<h2 id="page-title"><?= $this->title ?></h2>
			<?= $this->content ?>
		</div>
	</div>
</body>
</html>
