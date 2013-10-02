<?php

require_once 'Site/SiteAMQPApplication.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteCommandLineLogger.php';

class AMQPTestServer extends SiteAMQPApplication
{
	// {{{ protected function doWork()

	protected function doWork(SiteAMQPJob $job)
	{
		if ($job->getBody() === 'fail-test') {
			$this->logger->debug(' => sending test failure' . PHP_EOL);
			$job->sendFail('This is a test exception');
		} else  {
			$this->logger->debug(
				' => reversing "{string}"' . PHP_EOL,
				array(
					'string' => $job->getBody()
				)
			);
			$job->sendSuccess(strrev($job->getBody()));
		}
		sleep(1);
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config' => 'SiteConfigModule',
		);
	}

	// }}}
}

?>
