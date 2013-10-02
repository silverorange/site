<?php

require_once 'Site/SiteRedisModule.php';
require_once __DIR__.'/SiteRedisModuleTestApplication.php';

class SiteRedisModuleTest extends PHPUnit_Framework_TestCase
{
	protected $app = null;
	protected $redis = null;

	public function setUp()
	{
		$this->app = new SiteRedisModuleTestApplication(
			'redis-test',
			__DIR__.'/redis-test-application.ini'
		);

		$this->redis = new SiteRedisModule($this->app);
		$this->redis->init();
	}

	public function tearDown()
	{
		$this->redis->flushDB();
	}

	public function testGetAllKeys()
	{
		$this->redis->set('test', 'data');
		$this->assertEquals(
			array('site-redis-test:test'),
			$this->redis->getKeys('*')
		);
	}
}

?>
