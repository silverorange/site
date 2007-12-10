<?php

require_once 'Swat/SwatPercentageCellRenderer.php';

/**
 * Displays conversion rates that are NaN as a none-styled dash
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreConversionRateCellRenderer extends SwatPercentageCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->value === null) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'swat-none';
			$div_tag->style = 'text-align: center;';
			$div_tag->setContent('—');
			$div_tag->display();
		} else {
			if ($this->value > 1)
				$this->value = 1;

			parent::render();
		}
	}

	// }}}
}

?>
