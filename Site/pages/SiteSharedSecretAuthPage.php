<?php

/**
 * Page decorator that uses shared secret to check request authenticity.
 *
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSharedSecretAuthPage extends SitePageDecorator
{
    /**
     * List of GET variables we exclude from the MAC check.
     *
     * @var array
     */
    protected $exclude_names = ['mac', 'source', 'instance'];

    public function init()
    {
        if (!$this->isRequestAuthentic($this->getVariables())) {
            $key = $this->getHashKey();
            $message = $this->getHashMessage($this->getVariables());

            $expected = $this->getHashMac($message, $key);
            $provided = $_GET['mac'] ?? '';

            throw new SiteInvalidMacException(
                sprintf(
                    "Invalid message authentication code.\n\n" .
                    "Code expected: %s.\n" .
                    'Code provided: %s.',
                    $expected,
                    $provided
                )
            );
        }

        parent::init();
    }

    protected function getHashKey()
    {
        $api_key = $_GET['key'] ?? '';

        if ($api_key == '') {
            throw new SiteInvalidMacException('No API key provided.');
        }

        $class_name = SwatDBClassMap::get(SiteApiCredential::class);
        $credential = new $class_name();
        $credential->setDatabase($this->app->db);

        if (!$credential->loadByApiKey($api_key)) {
            throw new SiteInvalidMacException(
                sprintf(
                    'Unable to load shared secret for API key: %s.',
                    $api_key
                )
            );
        }

        return $credential->api_shared_secret;
    }

    protected function getVariables()
    {
        $vars = [];

        $exclude_names = $this->exclude_names;
        $exclude_names[] = $this->app->session->getSessionName();

        foreach ($_GET as $name => $value) {
            if (!in_array($name, $exclude_names)) {
                $vars[$name] = $value;
            }
        }

        return $vars;
    }

    protected function isRequestAuthentic($vars)
    {
        $key = $this->getHashKey();
        $message = $this->getHashMessage($vars);

        return (isset($_GET['mac']))
            && ($this->getHashMac($message, $key) === $_GET['mac']);
    }

    protected function getHashMessage($vars)
    {
        // Sort the varaibles into alphabetical order.
        ksort($vars, SORT_STRING);

        $message = '';

        foreach ($vars as $name => $value) {
            $message .= $name . $value;
        }

        return $message;
    }

    protected function getHashMac($message, $key)
    {
        return hash_hmac('sha256', $message, $key);
    }
}
