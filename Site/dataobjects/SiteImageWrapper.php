<?php

/**
 * A recordset wrapper class for SiteImage objects.
 *
 * Note: This recordset automatically loads image dimension bindings for
 *       images when constructed from a database result. If this behaviour is
 *       undesirable, set the lazy_load option to true.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see       SiteImage
 */
class SiteImageWrapper extends SwatDBRecordsetWrapper
{
    /**
     * @var string
     */
    protected $binding_table = 'ImageDimensionBinding';

    /**
     * @var string
     */
    protected $binding_table_image_field = 'image';

    public function initializeFromResultSet(MDB2_Result_Common $rs)
    {
        parent::initializeFromResultSet($rs);

        // automatically load bindings unless lazy_load is set to true
        if (!$this->getOption('lazy_load')) {
            $this->loadDimensionBindings();
        }
    }

    /**
     * Efficiently loads image dimension bindings for the images in this
     * recordset.
     *
     * Note: SiteImageWrapper automatically loads dimension bindings when
     *       constructed from a database result. This method is most useful
     *       when manually adding images to a recordset, or when using the
     *       <kbd>lazy_load</kbd> option.
     *
     * @param array|string $dimensions optional. A string or array of dimension
     *                                 shortnames to include. To include all
     *                                 dimensions use null. If not specified,
     *                                 all dimensions are included.
     */
    public function loadDimensionBindings($dimensions = null)
    {
        if (is_string($dimensions)) {
            $dimensions = [$dimensions];
        }

        if ($this->getCount() > 0
            && ($dimensions === null || count($dimensions) > 0)) {
            $image_ids = [];
            foreach ($this->getArray() as $image) {
                $image_ids[] = $this->db->quote($image->id, 'integer');
            }

            $sql = $this->getDimensionQuery($image_ids, $dimensions);
            $wrapper_class = $this->getImageDimensionBindingWrapperClassName();
            $bindings = SwatDB::query($this->db, $sql, $wrapper_class);

            if (count($bindings) == 0) {
                return;
            }

            $last_image = null;
            foreach ($bindings as $binding) {
                $field = $this->binding_table_image_field;

                if ($last_image === null
                    || $last_image->id !== $binding->{$field}) {
                    if ($last_image !== null) {
                        $wrapper->reindex();
                        $last_image->dimension_bindings = $wrapper;
                    }

                    $last_image = $this->getByIndex($binding->{$field});
                    $wrapper = new $wrapper_class();
                }

                $wrapper->add($binding);
            }

            $wrapper->reindex();
            $last_image->dimension_bindings = $wrapper;
        }
    }

    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(SiteImage::class);
        $this->index_field = 'id';
    }

    protected function getImageDimensionBindingWrapperClassName()
    {
        return SwatDBClassMap::get(SiteImageDimensionBindingWrapper::class);
    }

    protected function getDimensionQuery($image_ids, ?array $dimensions = null)
    {
        if ($dimensions === null) {
            $dimension_sql = '';
        } else {
            $dimension_shortnames = $dimensions;
            foreach ($dimension_shortnames as &$shortname) {
                $shortname = $this->db->quote($shortname, 'text');
            }

            $dimension_sql = sprintf(
                'and %s.dimension in (
				select id from ImageDimension where shortname in (%s))',
                $this->binding_table,
                implode(', ', $dimension_shortnames)
            );
        }

        $sql = sprintf(
            'select %1$s.*
			from %1$s
			where %1$s.%2$s in (%3$s) %4$s
			order by %2$s',
            $this->binding_table,
            $this->binding_table_image_field,
            implode(',', $image_ids),
            $dimension_sql
        );

        return $sql;
    }

    // deprecated

    /**
     * @deprecated use {@link SiteImageWrapper::loadDimensionBindings()}
     */
    public function loadDimensions(?array $dimensions = null)
    {
        $this->loadDimensionBindings($dimensions);
    }
}
