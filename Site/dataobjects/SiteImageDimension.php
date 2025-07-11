<?php

/**
 * An image dimension data object.
 *
 * @copyright 2008-2016 silverorange
 *
 * @property int           $id
 * @property ?string       $shortname
 * @property ?string       $title
 * @property ?int          $max_width
 * @property ?int          $max_height
 * @property bool          $crop
 * @property int           $dpi
 * @property int           $quality
 * @property bool          $strip
 * @property bool          $interlace
 * @property ?string       $resize_filter
 * @property bool          $upscale
 * @property SiteImageSet  $image_set
 * @property SiteImageType $image_type
 */
class SiteImageDimension extends SwatDBDataObject
{
    /**
     * Unique identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Short, textual identifer for this dimension.
     *
     * The shortname must be unique within this dimensions' set.
     *
     * @var string
     */
    public $shortname;

    /**
     * Title.
     *
     * @var string
     */
    public $title;

    /**
     * Maximum width in pixels.
     *
     * @var int
     */
    public $max_width;

    /**
     * Maximum height in pixels.
     *
     * @var int
     */
    public $max_height;

    /**
     * Crop.
     *
     * @var bool
     */
    public $crop;

    /**
     * DPI.
     *
     * @var int
     */
    public $dpi;

    /**
     * Quality.
     *
     * @var int
     */
    public $quality;

    /**
     * Strip embedded image data?
     *
     * @var bool
     */
    public $strip;

    /**
     * Save interlaced (progressive).
     *
     * @var bool
     */
    public $interlace;

    /**
     * Resize filter type.
     *
     * Specify which type of filter to use when resizing.
     * See the Imagick FILTER_* constants
     * {@link http://ca.php.net/manual/en/imagick.constants.php}.
     * If not defined, the default, 'FILTER_LANCZOS', is used.
     *
     * @var string
     */
    public $resize_filter;

    /**
     * Upscale?
     *
     * Whether or not to allow the dimension to upscale if the image width
     * or height is less than the dimension width or height.
     *
     * @var bool
     */
    public $upscale;

    private static $default_type_cache = [];

    /**
     * Loads a dimension from the database with a shortname.
     *
     * @param string $set_shortname       the shortname of the set
     * @param string $dimension_shortname the shortname of the dimension
     *
     * @return bool true if a dimension was successfully loaded and false if
     *              no dimension was found at the specified shortname
     */
    public function loadByShortname($set_shortname, $dimension_shortname)
    {
        $this->checkDB();

        $found = false;

        $sql = 'select * from %s where shortname = %s and image_set in
			(select id from ImageSet where shortname = %s)';

        $sql = sprintf(
            $sql,
            $this->table,
            $this->db->quote($dimension_shortname, 'text'),
            $this->db->quote($set_shortname, 'text')
        );

        $row = SwatDB::queryRow($this->db, $sql);

        if ($row !== null) {
            $this->initFromRow($row);
            $this->generatePropertyHashes();
            $found = true;
        }

        return $found;
    }

    protected function init()
    {
        $this->registerInternalProperty(
            'image_set',
            SwatDBClassMap::get(SiteImageSet::class)
        );

        $this->registerInternalProperty(
            'default_type',
            SwatDBClassMap::get(SiteImageType::class)
        );

        $this->table = 'ImageDimension';
        $this->id_field = 'integer:id';
    }

    protected function hasSubDataObject($key)
    {
        $found = parent::hasSubDataObject($key);

        if ($key === 'default_type' && !$found) {
            $default_type_id = $this->getInternalValue('default_type');

            if ($default_type_id !== null
                && array_key_exists($default_type_id, self::$default_type_cache)) {
                $this->setSubDataObject(
                    'default_type',
                    self::$default_type_cache[$default_type_id]
                );

                $found = true;
            }
        }

        return $found;
    }

    protected function setSubDataObject($name, $value)
    {
        if ($name === 'default_type') {
            self::$default_type_cache[$value->id] = $value;
        }

        parent::setSubDataObject($name, $value);
    }

    protected function getSerializableSubDataObjects()
    {
        return ['default_type'];
    }
}
