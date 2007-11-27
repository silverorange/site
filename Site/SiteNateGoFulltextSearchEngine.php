<?php

require_once 'Site/SiteFulltextSearchEngine.php';
require_once 'Site/SiteNateGoFulltextSearchResult.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';
require_once 'NateGoSearch/NateGoSearchPSpellSpellChecker.php';

/**
 * A fulltext search engine that uses NateGoSearch
 *
 * @package   Site
 * @copyright 2007 silverorange
 */
class SiteNateGoFulltextSearchEngine extends SwatObject
	implements SiteFulltextSearchEngine
{
	// {{{ protected properties

	/**
	 * The database
	 *
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * The locale to use for spell checking
	 *
	 * @var string
	 */
	protected $locale;

	/**
	 * The document types to search
	 *
	 * @var array
	 */
	protected $types = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new nate-go fulltext search engine
	 *
	 * @param MDB2_Driver_Common $db
	 * @param string $locale optional.
	 */
	public function __construct($db, $locale = null)
	{
		$this->db = $db;
		if ($locale === null) {
			$this->locale = setlocale(LC_ALL, '0');
		} else {
			$this->locale = $locale;
		}
	}

	// }}}
	// {{{ public function setTypes()

	/**
	 * Sets the document types to search
	 *
	 * @param array $types
	 */
	public function setTypes($types)
	{
		// TODO: if $types is not array throw an exception
		$this->types = $types;
	}

	// }}}
	// {{{ public function search()

	/**
	 * Perform a fulltext search and return the result
	 *
	 * @param string $keywords the keywords to search with.
	 *
	 * @return SiteFulltextSearchResult
	 */
	public function search($keywords)
	{
		$spell_checker = new NateGoSearchPSpellSpellChecker($this->locale);
		$query = new NateGoSearchQuery($this->db);
		$query->addBlockedWords(NateGoSearchQuery::getDefaultBlockedWords());
		$query->setSpellChecker($spell_checker);

		foreach ($this->types as $type)
			$query->addDocumentType($type);

		$result = $query->query($keywords);

		return new SiteNateGoFulltextSearchResult($this->db, $result);
	}

	// }}}
}

?>
