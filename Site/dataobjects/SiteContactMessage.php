<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * Contact message
 *
 * @package   Site
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteContactMessage extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique Identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Email address of the contacter
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Contact subject
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * The body of the contact message
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Whether or not this contact message is spam
	 *
	 * @var boolean
	 */
	public $spam = false;

	/**
	 * IP Address of the contact
	 *
	 * @var string
	 */
	public $ip_address;

	/**
	 * User agent of the HTTP client used by the contact
	 *
	 * @var string
	 */
	public $user_agent;

	/**
	 * Date this contact was created
	 *
	 * @var SwatDate
	 */
	public $createdate;

	/**
	 * Date this contact was sent
	 *
	 * @var SwatDate
	 */
	public $sent_date;

	/**
	 * Date this contact failed to send
	 *
	 * @var SwatDate
	 */
	public $error_date;

	// }}}
	// {{{ public function load()

	/**
	 * Loads this comment
	 *
	 * @param integer $id the database id of this contact message.
	 * @param SiteInstance $instance optional. The instance to load the contact
	 *                                message in. If the application does not
	 *                                use instances, this should be null. If
	 *                                unspecified, the instance is not checked.
	 *
	 * @return boolean true if this contact was loaded and false if it was not.
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
	// {{{ public static function getSubjects()

	public static function getSubjects()
	{
		$subjects = array(
			'general' => Site::_('General Question'),
			'website' => Site::_('Website'),
			'privacy' => Site::_('Privacy'),
		);

		return $subjects;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->registerDateProperty('createdate');
		$this->registerDateProperty('sent_date');
		$this->registerDateProperty('error_date');

		$this->table = 'ContactMessage';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
