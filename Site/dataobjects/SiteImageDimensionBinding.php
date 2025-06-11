<?php

/**
 * An image dimension binding data object.
 *
 * @copyright 2008-2016 silverorange
 *
 * @property int           $width
 * @property int           $height
 * @property ?int          $filesize
 * @property int           $dpi
 * @property bool          $on_cdn
 * @property int           $dimension
 * @property int           $image
 * @property SiteImageType $image_type
 */
class SiteImageDimensionBinding extends SwatDBDataObject
{
    /**
     * Width.
     *
     * @var int
     */
    public $width;

    /**
     * Height.
     *
     * @var int
     */
    public $height;

    /**
     * File size in bytes.
     *
     * @var int
     */
    public $filesize;

    /**
     * Dpi.
     *
     * @var int
     */
    public $dpi;

    /**
     * Whether or not this dimension is on a CDN.
     *
     * @var bool
     */
    public $on_cdn;

    /**
     * Dimension Id.
     *
     * This is not an internal property since alternative effiecient methods
     * are used to load dimensions and dimension bindings.
     *
     * @var int
     */
    public $dimension;

    /**
     * Image Id.
     *
     * This is not an internal property since alternative effiecient methods
     * are used to load dimensions and dimension bindings.
     *
     * @var int
     */
    public $image;

    /**
     * Image field name.
     */
    protected string $image_field = 'image';

    private static $image_type_cache = [];

    protected function init()
    {
        $this->table = 'ImageDimensionBinding';

        $this->registerInternalProperty(
            'image_type',
            SwatDBClassMap::get(SiteImageType::class)
        );
    }

    protected function hasSubDataObject($key)
    {
        $found = parent::hasSubDataObject($key);

        if ($key === 'image_type' && !$found) {
            $image_type_id = $this->getInternalValue('image_type');

            if ($image_type_id !== null
                && array_key_exists($image_type_id, self::$image_type_cache)) {
                $this->setSubDataObject(
                    'image_type',
                    self::$image_type_cache[$image_type_id]
                );

                $found = true;
            }
        }

        return $found;
    }

    protected function setSubDataObject($name, $value)
    {
        if ($name === 'image_type') {
            self::$image_type_cache[$value->id] = $value;
        }

        parent::setSubDataObject($name, $value);
    }

    /**
     * Saves this object to the database.
     *
     * Only modified properties are updated.
     */
    protected function saveInternal()
    {
        $sql = sprintf(
            'delete from %s where dimension = %s and %s = %s',
            $this->table,
            $this->db->quote($this->dimension, 'integer'),
            $this->image_field,
            $this->db->quote($this->image, 'integer')
        );

        SwatDB::exec($this->db, $sql);

        parent::saveInternal();
    }

    protected function getSerializablePrivateProperties()
    {
        return array_merge(parent::getSerializablePrivateProperties(), ['image_type']);
    }
}
