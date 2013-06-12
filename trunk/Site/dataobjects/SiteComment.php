<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SiteCommentFilter.php';
require_once 'NateGoSearch/NateGoSearch.php';

/**
 * A comment on a site
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteComment extends SwatDBDataObject
{
	// {{{ constants

	const STATUS_PENDING     = 0;
	const STATUS_PUBLISHED   = 1;
	const STATUS_UNPUBLISHED = 2;

	// }}}
	// {{{ public properties

	/**
	 * Unique Identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Fullname of person commenting
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * Link to display with the comment
	 *
	 * @var string
	 */
	public $link;

	/**
	 * Email address of the person commenting
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The body of this comment
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Visibility status
	 *
	 * Set using class contstants:
	 * STATUS_PENDING - waiting on moderation
	 * STATUS_PUBLISHED - comment published on site
	 * STATUS_UNPUBLISHED - not shown on the site
	 *
	 * @var integer
	 */
	public $status;

	/**
	 * Whether or not this comment is spam
	 *
	 * @var boolean
	 */
	public $spam = false;

	/**
	 * IP Address of the person commenting
	 *
	 * @var string
	 */
	public $ip_address;

	/**
	 * User agent of the HTTP client used to comment
	 *
	 * @var string
	 */
	public $user_agent;

	/**
	 * Date this comment was created
	 *
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ public static function getStatusTitle()

	public static function getStatusTitle($status)
	{
		switch ($status) {
		case self::STATUS_PENDING :
			$title = Site::_('Pending Approval');
			break;

		case self::STATUS_PUBLISHED :
			$title = Site::_('Shown on Site');
			break;

		case self::STATUS_UNPUBLISHED :
			$title = Site::_('Not Approved');
			break;

		default:
			$title = Site::_('Unknown Status');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public static function getStatusArray()

	public static function getStatusArray()
	{
		return array(
			self::STATUS_PUBLISHED =>
				self::getStatusTitle(self::STATUS_PUBLISHED),

			self::STATUS_PENDING => self::getStatusTitle(self::STATUS_PENDING),
			self::STATUS_UNPUBLISHED =>
				self::getStatusTitle(self::STATUS_UNPUBLISHED),
		);
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this comment
	 *
	 * @param integer $id the database id of this comment.
	 * @param SiteInstance $instance optional. The instance to load the comment
	 *                                in. If the application does not use
	 *                                instances, this should be null. If
	 *                                unspecified, the instance is not checked.
	 *
	 * @return boolean true if this comment and false if it was not.
	 */
	public function load($id, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null && $this->id_field !== null) {
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = sprintf('select %1$s.* from %1$s
				where %1$s.%2$s = %3$s',
				$this->table,
				$id_field->name,
				$this->db->quote($id, $id_field->type));

			$instance_id  = ($instance === null) ? null : $instance->id;
			if ($instance_id !== null) {
				$sql.=sprintf(' and instance %s %s',
					SwatDB::equalityOperator($instance_id),
					$this->db->quote($instance_id, 'integer'));
			}

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ public function postSave()

	/**
	 * Run post-save methods for the comment
	 *
	 * @param SiteApplication $app Site application
	 */
	public function postSave(SiteApplication $app)
	{
		if ($this->status == self::STATUS_PUBLISHED && !$this->spam) {
			$this->clearCache($app);
			$this->addToSearchQueue($app);
		}
	}

	// }}}
	// {{{ public function clearCache()

	public function clearCache(SiteApplication $app)
	{
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->registerDateProperty('createdate');

		$this->table = 'Comment';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue(SiteApplication $app)
	{
	}

	// }}}
}

?>
