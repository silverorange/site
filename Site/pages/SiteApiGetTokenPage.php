<?php

/**
 * Simple API page that provides sign on tokens to third parties.
 *
 * @copyright 2013-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteApiGetTokenPage extends SitePage
{
    protected function createLayout()
    {
        return new SiteLayout($this->app, SiteJSONTemplate::class);
    }

    // build phase

    public function build()
    {
        // Instruct shared proxies, like varnish, to not cache this response
        header('Cache-Control: s-maxage=0, must-revalidate');

        $response = $this->getJsonResponse(
            $this->getIdent(),
            $this->getVar('key')
        );

        $this->layout->startCapture('content');
        echo json_encode($response);
        $this->layout->endCapture();
    }

    protected function getVar($name)
    {
        return SiteApplication::initVar(
            $name,
            null,
            SiteApplication::VAR_GET
        );
    }

    protected function getIdent()
    {
        return $this->getVar('id');
    }

    protected function getJsonResponse($ident, $key)
    {
        $class_name = SwatDBClassMap::get(SiteApiCredential::class);
        $credential = new $class_name();
        $credential->setDatabase($this->app->db);

        if (!$credential->loadByApiKey($key)) {
            return ['status' => ['code' => 'error', 'message' => 'Invalid API key provided.']];
        }

        if ($ident == '') {
            return ['status' => ['code' => 'error', 'message' => 'Invalid unique identifier provided.']];
        }

        return $this->getSignOnToken($ident, $credential);
    }

    protected function getSignOnToken($ident, SiteApiCredential $credential)
    {
        $class_name = SwatDBClassMap::get(SiteApiSignOnToken::class);
        $token = new $class_name();
        $token->setDatabase($this->app->db);

        if (!$token->loadByIdent($ident, $credential)) {
            $token->ident = $ident;
            $token->api_credential = $credential->id;
            $token->token = uniqid();
            $token->createdate = new SwatDate();
            $token->createdate->toUTC();
            $token->save();
        }

        return ['status' => ['code' => 'ok', 'message' => ''], 'token' => $token->token];
    }
}
