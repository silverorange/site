<?php

/**
 * A comment on a site.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int                 $id
 * @property ?string             $fullname
 * @property ?string             $link
 * @property ?string             $email
 * @property string              $bodytext
 * @property int<self::STATUS_*> $status
 * @property ?string             $ip_address
 * @property ?string             $user_agent
 * @property SwatDate            $createdate
 * @property ?SiteInstance       $instance
 */
class SiteComment extends SwatDBDataObject
{
    public const STATUS_PENDING = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_UNPUBLISHED = 2;

    /**
     * Unique Identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Fullname of person commenting.
     *
     * @var string
     */
    public $fullname;

    /**
     * Link to display with the comment.
     *
     * @var string
     */
    public $link;

    /**
     * Email address of the person commenting.
     *
     * @var string
     */
    public $email;

    /**
     * The body of this comment.
     *
     * @var string
     */
    public $bodytext;

    /**
     * Visibility status.
     *
     * Set using class contstants:
     * STATUS_PENDING - waiting on moderation
     * STATUS_PUBLISHED - comment published on site
     * STATUS_UNPUBLISHED - not shown on the site
     *
     * @var int
     */
    public $status;

    /**
     * Whether or not this comment is spam.
     */
    public bool $spam = false;

    /**
     * IP Address of the person commenting.
     *
     * @var string
     */
    public $ip_address;

    /**
     * User agent of the HTTP client used to comment.
     *
     * @var string
     */
    public $user_agent;

    /**
     * Date this comment was created.
     *
     * @var SwatDate
     */
    public $createdate;

    public static function getStatusTitle($status)
    {
        return match ($status) {
            self::STATUS_PENDING     => Site::_('Pending Approval'),
            self::STATUS_PUBLISHED   => Site::_('Shown on Site'),
            self::STATUS_UNPUBLISHED => Site::_('Not Approved'),
            default                  => Site::_('Unknown Status'),
        };
    }

    public static function getStatusArray()
    {
        return [self::STATUS_PUBLISHED => self::getStatusTitle(self::STATUS_PUBLISHED), self::STATUS_PENDING => self::getStatusTitle(self::STATUS_PENDING), self::STATUS_UNPUBLISHED => self::getStatusTitle(self::STATUS_UNPUBLISHED)];
    }

    /**
     * Loads this comment.
     *
     * @param int          $id       the database id of this comment
     * @param SiteInstance $instance optional. The instance to load the comment
     *                               in. If the application does not use
     *                               instances, this should be null. If
     *                               unspecified, the instance is not checked.
     *
     * @return bool true if this comment and false if it was not
     */
    public function load($id, ?SiteInstance $instance = null)
    {
        $this->checkDB();

        $loaded = false;
        $row = null;
        if ($this->table !== null && $this->id_field !== null) {
            $id_field = new SwatDBField($this->id_field, 'integer');

            $sql = sprintf(
                'select %1$s.* from %1$s
				where %1$s.%2$s = %3$s',
                $this->table,
                $id_field->name,
                $this->db->quote($id, $id_field->type)
            );

            $instance_id = ($instance === null) ? null : $instance->id;
            if ($instance_id !== null) {
                $sql .= sprintf(
                    ' and instance %s %s',
                    SwatDB::equalityOperator($instance_id),
                    $this->db->quote($instance_id, 'integer')
                );
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

    /**
     * Run post-save methods for the comment.
     *
     * @param SiteApplication $app Site application
     */
    public function postSave(SiteApplication $app)
    {
        $this->clearCache($app);
    }

    public function clearCache(SiteApplication $app) {}

    protected function init()
    {
        $this->registerInternalProperty(
            'instance',
            SwatDBClassMap::get(SiteInstance::class)
        );

        $this->registerDateProperty('createdate');

        $this->table = 'Comment';
        $this->id_field = 'integer:id';
    }
}
