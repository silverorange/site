<?php

require_once __DIR__.'/vendor/autoload.php';

class AMQPTestClient extends SiteCommandLineApplication
{
	// {{{ public function run()

	public function run()
	{
		$this->initModules();
		$this->parseCommandLineArguments();

		$strings = array(
			'test',
			'abcdefg',
			'fail-test',
		);

		$this->debug("Async test:\n", true);

		foreach ($strings as $string) {
			$this->debug($string."\n");
			$this->amqp->doAsync('strrev', $string);
		}

		$this->debug("\n");
		$this->debug("Sync test:\n", true);

		try {
			foreach ($strings as $string) {
				$this->debug($string.' => ');
				$this->debug($this->amqp->doSync('strrev', $string));
				$this->debug("\n");
			}
		} catch (SiteAMQPJobFailureException $e) {
			$this->debug("CAUGHT EXPECTED FAIL\n");
		}

		$this->debug("\n");
		$this->debug("done\n", true);
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config' => 'SiteConfigModule',
			'amqp'   => 'SiteAMQPModule',
		);
	}

	// }}}
}

?>
