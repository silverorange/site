<?php

require_once 'Swat/SwatForm.php';

/**
 * Custom form with overridden displayHiddenFields() method to not display
 * any hidden fields
 *
 * This is useful if the form's method is set to HTTP GET.
 *
 * @package   Site
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SwatForm::setMethod()
 */
class SiteSearchForm extends SwatForm
{
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();

		$this->setMethod(SwatForm::METHOD_GET);
	}

	// }}}
	// {{{ public function isSubmitted()

	public function isSubmitted()
	{
		/*
		 * Search forms do not output an hidden field to determine if they
		 * have been submitted.  Instead they are always assummed to be
		 * submitted.
		 */
		return true;
	}

	// }}}
	// {{{ protected function displayHiddenFields()

	protected function displayHiddenFields(SwatDisplayContext $context)
	{
		/*
		 * Override to not output any hidden fields since search forms use the
		 * HTTP GET method.
		 */
	}

	// }}}
}

?>
