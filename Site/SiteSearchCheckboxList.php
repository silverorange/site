<?php

/**
 * A checkbox list widget for search forms
 *
 * @package   Site
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteSearchCheckboxList extends  SwatCheckboxList
{


	public $highlight_values = [];




	/**
	 * Processes this checkbox list widget
	 */
	public function process()
	{
		$form = $this->getForm();

		SwatOptionControl::process();

		$this->getCompositeWidget('check_all')->process();

		$data = &$form->getFormData();

		$this->processValues();
	}




	protected function getLiTag($option)
	{
		$tag = parent::getLiTag($option);

		if (in_array($option->value, $this->highlight_values))
			$tag->class = 'highlight';

		return $tag;
	}


}

?>
