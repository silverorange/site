<?php

require_once 'Site/SiteMultipartMailMessage.php';

/**
 * Multipart text/html email message with replacement marker support
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteReplacementMarkerMailMessage extends SiteMultipartMailMessage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		parent::__construct($app);

		$this->smtp_server = $app->config->email->smtp_server;
		$this->subject     = $this->replaceMarkers($this->getSubject());
		$this->text_body   = $this->replaceMarkers($this->getBodyText());
	}

	// }}}
	// {{{ abstract protected function getSubject()

	abstract protected function getSubject();

	// }}}
	// {{{ abstract protected function getBodyText()

	abstract protected function getBodyText();

	// }}}
	// {{{ protected function getReplacementMarkerText()

	/**
	 * Gets replacement text for a specfied replacement marker identifier
	 *
	 * @param string $marker_id the id of the marker found in the text
	 *
	 * @return string the replacement text for the given marker id.
	 */
	protected function getReplacementMarkerText($marker_id)
	{
		// by default, always return a blank string as replacement text
		return '';
	}

	// }}}
	// {{{ protected final function replaceMarkers()

	/**
	 * Replaces markers in text with dynamic content
	 *
	 * @param string $text the text of the email.
	 *
	 * @return string the text with markers replaced by dynamic content.
	 *
	 * @see SitePage::getReplacementMarkerText()
	 */
	protected final function replaceMarkers($text)
	{
		$marker_pattern = '/\[(.*?)\]/u';
		$callback = array($this, 'getReplacementMarkerTextByMatches');
		return preg_replace_callback($marker_pattern, $callback, $text);
	}

	// }}}
	// {{{ private final function getReplacementMarkerTextByMatches()

	/**
	 * Gets replacement text for a replacement marker from within a matches
	 * array returned from a PERL regular expression
	 *
	 * @param array $matches the PERL regular expression matches array.
	 *
	 * @return string the replacement text for the first parenthesized
	 *                 subpattern of the <i>$matches</i> array.
	 */
	private final function getReplacementMarkerTextByMatches($matches)
	{
		if (isset($matches[1]))
			return $this->getReplacementMarkerText($matches[1]);

		return '';
	}

	// }}}
}

?>
