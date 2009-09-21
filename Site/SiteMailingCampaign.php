<?php

require_once 'Site/SiteMailingList.php';
require_once 'Site/SiteLayoutData.php';

/**
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMailingCampaign
{
	// {{{ class constants

	/**
	 * Output formats
	 */
	const FORMAT_XHTML = 1;
	const FORMAT_TEXT  = 2;

	// }}}
	// {{{ public properties

	public $shortname;

	// }}}
	// {{{ protected properties

	protected $app;

	/**
	 * @var SiteLayoutData
	 */
	protected $data;

	protected $xhtml_template_filename = 'template-html.php';
	protected $text_template_filename  = 'template-text.php';

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, $shortname)
	{
		$this->app       = $app;
		$this->shortname = $shortname;
		$this->data      = new SiteLayoutData();
	}

	// }}}

	// {{{ public function getAnalyticsKey()

	public function getAnalyticsKey()
	{
		$key = $this->shortname;

		return $key;
	}

	// }}}
	// {{{ public function getFromAddress()

	public function getFromAddress()
	{
		return null;
		// TODO: default to a config setting
	}

	// }}}
	// {{{ public function getFromName()

	public function getFromName()
	{
		return null;
	}

	// }}}
	// {{{ public function getSubject()

	public function getSubject()
	{
		return null;
	}

	// }}}
	// {{{ public final function getContent()

	/**
	 * Gets the content of this mailing
	 *
	 * @param string $filename the filename of the template to use.
	 * @param integer $format integer contstant of the output format to use.
	 *
	 * @return string the content.
	 */
	public final function getContent($format = self::FORMAT_XHTML)
	{
		$filename = $this->getTemplateFilename($format);
		$this->build($format);

		ob_start();
		$this->data->display($filename);
		$content = ob_get_clean();
		$content = $this->replaceMarkers($content);
		$content = $this->transform($content, $format);

		return $content;
	}

	// }}}
	// {{{ protected function build()

	/**
	 * Builds data properties before they are substituted into the layout
	 */
	protected function build($format)
	{
	}

	// }}}
	// {{{ protected function transform()

	protected function transform($content, $format) {
		switch ($format) {
		case self::FORMAT_XHTML:
			$document = $this->getDomDocument($content);
			$this->transformXhtml($document);
			$content = $document->saveXML(
				$document->documentElement, LIBXML_NOXMLDECL);

			break;

		case self::FORMAT_TEXT:
			$content = $this->transformText($content);
			break;
		}

		return $content;
	}

	// }}}
	// {{{ protected function transformXhtml()

	protected function transformXhtml($document)
	{
		$head_tags = $document->documentElement->getElementsByTagName('head');
		$head = $head_tags->item(0);

		// add base element to head
		$base = $document->createElement('base');
		$base->setAttribute('href', $this->getBaseHref());
		$head->insertBefore($base, $head->firstChild);

		// add analytics uri vars to all anchors in the rendered document
		$anchors = $document->documentElement->getElementsByTagName('a');
		foreach ($anchors as $anchor) {
			$href = $anchor->getAttribute('href');
			if (substr($href, 0, 2) != '*|') {
				$href = $this->appendAnalyticsToUri($href);
				$anchor->setAttribute('href', $href);
			}
		}
	}

	// }}}
	// {{{ protected function transformText()

	/**
	 * Mangles links to have ad tracking vars
	 */
	protected function transformText($text)
	{
		// prepend uris with base href
		$text = preg_replace('/:uri:(.*?)(\s)/',
			$this->getBaseHref().'\1\2', $text);

		if (mb_detect_encoding($text, 'UTF-8', true) !== 'UTF-8')
			throw new SiteException('Text output is not valid UTF-8');

		return $text;
	}

	// }}}

	// {{{ protected function getBaseHref()

	protected function getBaseHref()
	{
		return $this->app->config->uri->absolute_base;
	}

	// }}}
	// {{{ protected function getResourceBaseHref()

	protected function getResourceBaseHref()
	{
		$base_href = $this->app->config->uri->absolute_resource_base;

		if ($base_href === null) {
			$base_href = $this->getBaseHref();
		}

		return $base_href;
	}

	// }}}
	// {{{ private function getDomDocument()

	private function getDomDocument($xhtml)
	{
		$internal_errors = libxml_use_internal_errors(true);

		$document = new DOMDocument();
		if (!$document->loadXML($xhtml)) {
			$xml_errors = libxml_get_errors();
			$message = '';
			foreach ($xml_errors as $error)
				$message.= sprintf("%s in %s, line %d\n",
					trim($error->message),
					$error->file,
					$error->line);

			libxml_clear_errors();
			libxml_use_internal_errors($internal_errors);

			$e = new Exception("Generated XHTML is not valid:\n".
				$message);

			throw $e;
		}

		libxml_use_internal_errors($internal_errors);

		return $document;
	}

	// }}}
	// {{{ protected function getCustomAnalyticsUriVars()

	protected function getCustomAnalyticsUriVars()
	{
		$vars = array();

		return $vars;
	}

	// }}}
	// {{{ protected function appendAnalyticsToUri()

	protected function appendAnalyticsToUri($uri)
	{
		$vars = array();

		foreach ($this->getCustomAnalyticsUriVars() as $name => $value)
			$vars[] = sprintf('%s=%s', urlencode($name), urlencode($value));

		if (count($vars)) {
			$var_string = implode('&', $vars);

			if (strpos($uri, '?') === false)
				$uri = $uri.'?'.$var_string;
			else
				$uri = $uri.'&'.$var_string;
		}

		return $uri;
	}

	// }}}
	// {{{ protected function getSourceDirectory()

	protected function getSourceDirectory()
	{
		return 'bogus';
	}

	// }}}
	// {{{ protected function getTemplateFilename()

	protected function getTemplateFilename($format)
	{
		$filename = $this->getSourceDirectory().'/';

		switch($format) {
		case SiteMailingCampaign::FORMAT_XHTML:
			$filename.= $this->xhtml_template_filename;
			break;

		case SiteMailingCampaign::FORMAT_TEXT:
			$filename.= $this->text_template_filename;
			break;
		}

		return $filename;
	}

	// }}}
	// {{{ protected function getReplacementMarkerText()

	/**
	 * Gets replacement text for a specfied replacement marker identifier
	 *
	 * @param string $marker_id the id of the marker found in the campaign
	 *                           content.
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
	 * Replaces markers in campaign with dynamic content
	 *
	 * @param string $text the content of the campaign.
	 *
	 * @return string the campaign content with markers replaced by dynamic
	 *                 content.
	 *
	 * @see SitePage::getReplacementMarkerText()
	 */
	protected final function replaceMarkers($text)
	{
		$marker_pattern = '/<!-- \[(.*?)\] -->/u';
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
