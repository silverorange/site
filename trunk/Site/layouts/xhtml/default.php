<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

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
