<?php

/**
 * Thrown when the path we're looking up has invalid UFF-8 in it.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SitePathInvalidUtf8Exception extends SiteException
{
    /**
     * @var string
     */
    protected $path = '';

    public function __construct($message, $code = 0, $raw_path = '')
    {
        parent::__construct($message, $code);
        $this->path = $raw_path;
    }

    public function getRawPath()
    {
        return $this->path;
    }

    public function getEscapedPath()
    {
        return SwatString::escapeBinary($this->getRawPath());
    }
}
