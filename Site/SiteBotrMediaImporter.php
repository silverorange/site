<?php

require_once 'Swat/SwatViewSelection.php';
require_once 'Site/SiteBotrMediaToasterCommandLineApplication.php';
require_once 'Site/dataobjects/SiteBotrMedia.php';
require_once 'Site/dataobjects/SiteBotrMediaSet.php';

/**
 * Application to import new media from BOTR into the Media DB tables
 *  - Check to make sure all encoding is done. If not wait till done.
 *  - Add Media and MediaEncodingBinding's to the database.
 *  - Mark Media as imported.
 *
 * @package   Site
 * @copyright 2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      Support for importing into multiple MediaSets (perhaps based on
 *            the encoding profiles present on the Media we're importing).
 */
class SiteBotrMediaImporter extends SiteBotrMediaToasterCommandLineApplication
{
	// {{{ protected properties

	/**
	 * Shortname of the MediaSet to import into
	 *
	 * @var string
	 */
	protected $media_set_shortname;

	/**
	 * MediaSet to use when importing files.
	 *
	 * All media is not necessarily this MediaSet, but we import everything
	 * as that one MediaSet. Admin tools then allow for updating the Media to
	 * belong to another MediaSet. This only works when the MediaSet's share the
	 * same encodings, and will have to be rethought when that is no longer
	 * true.
	 *
	 * @var SiteBotrMediaSet
	 */
	protected $media_set;

	/**
	 * Array of BOTR keys that have already been imported and exist in the Media
	 * table.
	 *
	 * @var array
	 */
	protected $existing_keys;

	/**
	 * Array of media that needs to be imported.
	 *
	 * @var array
	 */
	protected $media_to_import;

	/**
	 * Array of media that has been imported, but that we want to check for new
	 * encoding bindings.
	 *
	 * @var array
	 */
	protected $media_to_recheck;

	/**
	 * Count of files imported into the database
	 *
	 * @var integer
	 */
	protected $imported_count = 0;

	/**
	 * Count of files that had encodings added.
	 *
	 * @var integer
	 */
	protected $encodings_added_count = 0;

	/**
	 * Count of files already imported into the database
	 *
	 * @var integer
	 */
	protected $already_imported_count = 0;

	/**
	 * Whether or not to force import of all files on bits on the run.
	 *
	 * If true, this resets all the media on bits on the run as not imported, which
	 * causes all media to be reimported. Media already in our database will be marked as
	 * imported, new media will be imported.
	 *
	 * @var boolean
	 */
	private $force_import = false;

	// }}}
	// {{{ public function __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		$force_import = new SiteCommandLineArgument(
			array('--force-import'),
			'setForceImport',
			'Optional. Forces all files to be re-imported, even if they have '.
			'been previously imported.');

		$this->addCommandLineArgument($force_import);

		parent::__construct($id, $filename, $title, $documentation);
	}

	// }}}
	// {{{ public function setForceImport()

	public function setForceImport()
	{
		$this->force_import = true;
	}

	// }}}
	// {{{ public function setMediaSet()

	public function setMediaSet($media_set_shortname)
	{
		$this->media_set_shortname = $media_set_shortname;
		$this->initMediaSet();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->initDataObjectTemplates();
	}

	// }}}
	// {{{ protected function initMediaSet()

	protected function initMediaSet()
	{
		$media_set_class = SwatDBClassMap::get('SiteBotrMediaSet');
		$this->media_set = new $media_set_class();
		$this->media_set->setDatabase($this->db);

		if (!$this->media_set->loadByShortname($this->media_set_shortname,
			$this->getInstance())) {
			throw new SiteCommandLineException(sprintf(
				'No media set exists with shortname ‘%s’.',
				$this->media_set_shortname));
		}
	}

	// }}}
	// {{{ protected function initDataObjectTemplates()

	protected function initDataObjectTemplates()
	{
		$media_class = SwatDBClassMap::get('SiteBotrMedia');
		$this->media_object_template = new $media_class();
		$this->media_object_template->setDatabase($this->db);
		$this->media_object_template->downloadable = true;
		$this->media_object_template->media_set    = clone $this->media_set;

		$binding_class = SwatDBClassMap::get('SiteBotrMediaEncodingBinding');
		$this->media_encoding_binding_template = new $binding_class();
		$this->media_encoding_binding_template->setDatabase($this->db);
	}

	// }}}
	// {{{ protected function runInternal()

	protected function runInternal()
	{
		$this->debug("Importing Media...\n");

		if ($this->force_import) {
			$this->resetMedia();
		}

		$this->initMediaToImport();

		if (count($this->media_to_import)) {
			$this->importMedia();
		}

		if (count($this->media_to_recheck)) {
			$this->importMissingMediaEncodings();
		}

		$this->displayResults();
	}

	// }}}
	// {{{ protected function resetMedia()

	protected function resetMedia()
	{
		$media = $this->getMedia();

		$this->debug(sprintf("Resetting %s media files on BOTR ... ",
			$this->locale->formatNumber(count($media))));

		foreach ($media as $media_file) {
			$this->toaster->updateMediaRemoveTagsByKey($media_file['key'],
				array($this->imported_tag));
		}

		$this->resetMediaCache();

		$this->debug("done.\n");
	}

	// }}}
	// {{{ protected function initMediaToImport()

	protected function initMediaToImport()
	{
		$encoded_count = 0;
		$media         = $this->getMedia();

		foreach ($media as $media_file) {
			if ($this->mediaFileIsIgnorable($media_file)) {
				// don't import files that are marked as ignorable (aka deleted
				// or ignored). we should make this part of the debugged display
			} elseif ($this->mediaFileIsMarkedEncoded($media_file)) {
				$encoded_count++;
				// media tagged "imported" have already been processed by this
				// script. As well, Media that already exist in the media table
				// can't be reimported.
				if ($this->force_import ||
					strpos($media_file['tags'], $this->imported_tag) ===
					false) {
					if ($this->mediaRowExists($media_file['key']) === true) {
						$this->already_imported_count++;
						$this->media_to_recheck[] = $media_file;
					} else {
						$this->media_to_import[] = $media_file;
					}
				} else {
					$this->already_imported_count++;
				}
			}
		}

		$this->debug(sprintf("%s files on BOTR, %s completely encoded.\n".
			"%s files already imported, %s new files to import, %s to recheck.".
			"\n\n",
			$this->locale->formatNumber(count($media)),
			$this->locale->formatNumber($encoded_count),
			$this->locale->formatNumber($this->already_imported_count),
			$this->locale->formatNumber(count($this->media_to_import)),
			$this->locale->formatNumber(count($this->media_to_recheck))));
	}

	// }}}
	// {{{ protected function mediaRowExists()

	protected function mediaRowExists($key)
	{
		if ($this->existing_keys === null) {
			$this->existing_keys = SwatDB::getOptionArray($this->db, 'Media',
				'key', 'id');
		}

		return (array_search($key, $this->existing_keys) !== false);
	}

	// }}}
	// {{{ protected function importMedia()

	protected function importMedia()
	{
		foreach($this->media_to_import as $media_file) {
			$this->debug(sprintf('Media: %s ... ',
				$media_file['key']));

			$transaction = new SwatDBTransaction($this->db);
			try {
				$this->saveMedia($media_file);
				$this->updateMediaOnBotr($media_file);
				$this->imported_count++;
				$transaction->commit();
			} catch (Exception $e) {
				$transaction->rollback();
				throw $e;
			}

			$this->debug("done.\n");
		}

		$this->debug("\n");
	}

	// }}}
	// {{{ protected function importMissingMediaEncodings()

	protected function importMissingMediaEncodings()
	{
		$this->initMediaObjects();

		foreach($this->media_to_recheck as $media_file) {
			$this->debug(sprintf('Media: %s ... ',
				$media_file['key']));

			$transaction = new SwatDBTransaction($this->db);
			try {
				$this->updateBindings($media_file);
				$this->updateMediaOnBotr($media_file);
				$transaction->commit();
			} catch (Exception $e) {
				$transaction->rollback();
				throw $e;
			}

			$this->debug("done.\n");
		}

		$this->debug("\n");
	}

	// }}}
	// {{{ protected function saveMedia()

	protected function saveMedia(array $media_file)
	{
		$media_object = $this->getMediaObject($media_file);
		$media_object->save();

		$imported_count = 0;
		$encodings = $this->toaster->getEncodingsByKey($media_file['key']);
		foreach($encodings as $encoding) {
			// originals aren't saved in the db as EncodingBindings, so ignore
			if ($encoding['template']['format']['key'] != 'original') {
				$binding_object = $this->getMediaEncodingBindingObject(
					$media_object, $encoding);

				$binding_object->save();
				$imported_count++;
			}
		}

		$this->debug(sprintf("%s encodings imported ... ",
			$this->locale->formatNumber($imported_count)));
	}

	// }}}
	// {{{ protected function updateBindings()

	protected function updateBindings($media_file)
	{
		$encodings = $this->toaster->getEncodingsByKey($media_file['key']);
		$existing_media_objects = $this->getMediaObjects();

		$existing_count = count($encodings);
		$added_count    = 0;

		$this->debug(sprintf('found %s encodings ... ',
			$this->locale->formatNumber($existing_count)));

		foreach ($encodings as $encoding) {
			// we ignore originals
			if ($encoding['template']['format']['key'] != 'original') {
				$media_object = $existing_media_objects[$media_file['key']];
				if (!$media_object->encodingExists($encoding['width'])) {
					$added_count++;
					$existing_count--;
					$this->encodings_added_count++;

					$binding_object = $this->getMediaEncodingBindingObject(
						$media_object, $encoding);

					$binding_object->save();
				}
			}
		}

		$this->debug(sprintf("%s already exist, %s inserted... ",
			$this->locale->formatNumber($existing_count),
			$this->locale->formatNumber($added_count)));
	}

	// }}}

	// {{{ protected function getMediaObject()

	protected function getMediaObject(array $media_file)
	{
		$media_object = clone $this->media_object_template;
		$media_object->title = $this->getTitle($media_file);
		$media_object->key   = $media_file['key'];

		// duration is stored with micro-seconds on BOTR, round to the closest
		// full second.
		$media_object->duration = intval(round($media_file['duration']));

		// date is stored as UTC timestamp on BOTR
		$media_object->createdate = new SwatDate();
		$media_object->createdate->setTimestamp($media_file['date']);
		$media_object->createdate->toUTC();

		$media_object->original_filename =
			$media_file['custom']['original_filename'];

		return $media_object;
	}

	// }}}
	// {{{ protected function getMediaEncodingBindingObject()

	protected function getMediaEncodingBindingObject(
		SiteBotrMedia $media_object, array $encoding)
	{
		$media_encoding = $this->media_set->getEncodingByShortname(
			$encoding['width']);

		$binding_object = clone $this->media_encoding_binding_template;
		$binding_object->media          = $media_object->id;
		$binding_object->media_encoding = $media_encoding->id;
		$binding_object->media_type     = $media_encoding->default_type;
		$binding_object->key            = $encoding['key'];
		$binding_object->filesize       = $encoding['filesize'];
		$binding_object->width          = $encoding['width'];
		$binding_object->height         = $encoding['height'];

		return $binding_object;
	}

	// }}}
	// {{{ protected function getTitle()

	protected function getTitle($media_file)
	{
		$info = pathinfo($media_file['title']);
		return $info['filename'];
	}

	// }}}
	// {{{ protected function updateMediaOnBotr()

	protected function updateMediaOnBotr(array $media_file)
	{
		$this->toaster->updateMediaAddTagsByKey(
			$media_file['key'],
			array($this->imported_tag));
	}

	// }}}
	// {{{ protected function getMediaObjectWhere()

	protected function getMediaObjectWhere()
	{
		$where = parent::getMediaObjectWhere();

		$where.= sprintf(' and key in (%s)',
			SwatDB::implodeSelection($this->db,
				new SwatViewSelection($this->existing_keys),
				'text'));

		return $where;
	}

	// }}}
	// {{{ protected function displayResults()

	protected function displayResults()
	{
		$this->debug(sprintf(
			"%s media files found, %s files already imported.\n".
			"%s to recheck, %s new bindings added.\n".
			"%s ready to import, %s successfully imported.\n\n",
			$this->locale->formatNumber(count($this->getMedia())),
			$this->locale->formatNumber($this->already_imported_count),
			$this->locale->formatNumber(count($this->media_to_recheck)),
			$this->locale->formatNumber($this->encodings_added_count),
			$this->locale->formatNumber(count($this->media_to_import)),
			$this->locale->formatNumber($this->imported_count)));
	}

	// }}}
}

?>
