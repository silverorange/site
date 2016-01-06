<?php

namespace Silverorange\Autoloader;

$package = new Package('silverorange/site');

$package->addRule(
	new Rule(
		'pages',
		'Site',
		array('Page', 'Server', 'PageDecorator')
	)
);

$package->addRule(new Rule('gadgets', 'Site', 'Gadget'));
$package->addRule(new Rule('layouts', 'Site', 'Layout'));
$package->addRule(new Rule('views', 'Site', 'View'));
$package->addRule(new Rule('exceptions', 'Site', 'Exception'));

$package->addRule(
	new Rule(
		'dataobjects',
		'Site',
		array(
			'Binding',
			'Wrapper',
			'AccountLoginHistory',
			'AccountLoginSession',
			'Account',
			'Ad',
			'AdReferrer',
			'ApiCredential',
			'Article',
			'AttachmentCdnTask',
			'Attachment',
			'AttachmentSet',
			'AudioMedia',
			'BotrMediaEncoding',
			'BotrMedia',
			'BotrMediaPlayer',
			'BotrMediaSet',
			'CdnTask',
			'Comment',
			'ContactMessage',
			'GadgetCache',
			'GadgetInstance',
			'GadgetInstanceSettingValue',
			'ImageCdnTask',
			'ImageDimension',
			'Image',
			'ImageSet',
			'ImageType',
			'InstanceConfigSetting',
			'Instance',
			'MediaCdnTask',
			'MediaEncoding',
			'Media',
			'MediaSet',
			'MediaType',
			'SignOnToken',
			'VideoImage',
			'VideoMediaEncoding',
			'VideoMedia',
			'VideoMediaSet',
			'VideoScrubberImage',
		)
	)
);

$package->addRule(new Rule('', 'Site'));

Autoloader::addPackage($package);

?>
