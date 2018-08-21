<?php

/**
 * @package   Site
 * @copyright 2018 silverorange
 */
class SitePayAPI
{
  /**
	 * API username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * API password
	 *
	 * @var string
	 */
	protected $password;

  /**
   * API endpoint
   *
   * @var string
   */
  protected $endpoint;

	/**
	 * API value
	 *
	 * @var string
	 */
	private $data;

	/**
	 * A convenience reference to the Guzzle client
	 *
	 * @var GuzzleHttp\Client
	 */
	private $client;

	// {{{ public function __construct()
	public function __construct($username, $password, $endpoint, $data)
	{
		$this->username = $username;
		$this->password = $password;
		$this->token    = $token;
		$this->client = new GuzzleHttp\Client([
			'base_uri' => $endpoint
		]);
		$this->data = null;
		$this->loadMembership($id);
	}

}

?>
