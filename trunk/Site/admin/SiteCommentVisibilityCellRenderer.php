<?php

require_once 'Swat/SwatCellRenderer.php';

/**
 * A cell renderer for rendering visibility of site comments
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentVisibilityCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	public $status;
	public $spam;

	// }}}
	// {{{ public function render

	public function render()
	{
		if ($this->spam) {
			$color = 'Red';
			$title = Site::_('Spam');

		} else {
			$title = SiteComment::getStatusTitle($this->status);

			switch ($this->status) {
			case (SiteComment::STATUS_UNPUBLISHED):
				$color = 'Red';
				break;

			case (SiteComment::STATUS_PUBLISHED):
				$color = 'Green';
				break;

			case (SiteComment::STATUS_PENDING):
				$color = 'Yellow';
				break;
			}
		}

		// TODO: output the title in the color, or use some sort of visual
		// representation for the statuses, like a traffic light
		echo $title;
	}

	// }}}
}

?>
