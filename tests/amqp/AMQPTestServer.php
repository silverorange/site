<?php

require_once __DIR__.'/vendor/autoload.php';

class AMQPTestServer extends SiteAMQPApplication
{


	protected function doWork(SiteAMQPJob $job)
	{
		if ($job->getBody() === 'fail-test') {
			$this->logger->debug(' => sending test failure'.PHP_EOL);
			$job->sendFail('This is a test exception');
		} else  {
			$this->logger->debug(
				' => reversing "{string}"'.PHP_EOL,
				['string' => $job->getBody()]
			);
			$job->sendSuccess(strrev($job->getBody()));
		}
		sleep(1);
	}




	protected function getDefaultModuleList()
	{
		return ['config' => 'SiteConfigModule'];
	}


}

?>
