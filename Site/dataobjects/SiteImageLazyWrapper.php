<?php

/**
 * An recordset wrapper class for SiteImage objects that doesn't automatically
 * load dimension bindings.
 *
 * This is deprecated.
 *
 * @copyright  2010-2013 silverorange
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see        SiteImage
 * @see        SiteImageWrapper
 * @deprecated {@link SiteImageWrapper} can do lazy loading using the
 *             <kbd>lazy_load</kbd> recordset wrapper option
 */
class SiteImageLazyWrapper extends SiteImageWrapper
{
    public function __construct(
        ?MDB2_Reslt_Common $rs = null,
        array $options = []
    ) {
        $options = array_merge(
            $options,
            ['lazy_load' => true]
        );

        parent::__construct($rs, $options);
    }
}
