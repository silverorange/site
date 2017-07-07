<?php

/**
 * @package   Site
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteDefaultTemplate implements SiteTemplateInterface
{
	// {{{ public function display()

	public function display(SiteLayoutData $data)
	{
		echo <<<'HTML'
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <html class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <html class="ie8"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> <html> <!--<![endif]-->

<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<base href="{$data->basehref}" />
	{$data->html_head_entries}
	<title>{$data->html_title}</title>
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<meta name="description" content="{$data->meta_description}" />
	<meta name="ids" content="{$data->meta_keywords}" />
</head>

<body>
	<div id="main-content">
		<div id="body-content">
			<h2 id="page-title">{$data->title}</h2>
			{$data->content}
		</div>
	</div>
</body>
</html>

HTML;
	}

	// }}}
}

?>
