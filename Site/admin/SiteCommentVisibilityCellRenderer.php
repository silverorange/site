<?php

/**
 * A cell renderer for rendering visibility of site comments.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentVisibilityCellRenderer extends SwatCellRenderer
{
    public $status;
    public $spam;

    public function render(): void
	{
		echo $this->spam
			? Site::_('Spam')
			: SiteComment::getStatusTitle($this->status);
    }
}
