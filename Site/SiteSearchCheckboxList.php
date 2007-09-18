<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatCheckboxList.php';

/**
 * A checkbox list widget for search forms
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSearchCheckboxList extends  SwatCheckboxList
{
	// {{{ public function process()

	/**
	 * Processes this checkbox list widget
	 */
	public function process()
	{
		$form = $this->getForm();

		SwatOptionControl::process();

		$this->check_all->process();

		$data = &$form->getFormData();

		if (isset($data[$this->id]))
			$this->values = $data[$this->id];
		else
			$this->values = array();
	}

	// }}}
}

?>
