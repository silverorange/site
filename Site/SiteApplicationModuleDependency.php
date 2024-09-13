<?php

/**
 * Dependency object for an application modules
 *
 * Dependencies may be optional or required. Required dependencies require
 * another module to provide the dependent feature. Both required and optional
 * dependencies affect the order in which default modules are added to the
 * application. Only required dependencies prevent adding a module when a
 * dependenct feature is not provided.
 *
 * @package   Site
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteApplicationModule
 */
class SiteApplicationModuleDependency extends SwatObject
{


	/**
	 * The dependent feature of this dependency
	 *
	 * @var string
	 */
	protected $feature;

	/**
	 * Whether or not the dependent feature is required
	 *
	 * If false, the dependent feature is optional.
	 */
	protected $required = true;




	/**
	 * Creates a new module dependency
	 *
	 * @param string $feature the dependent feature.
	 * @param boolean $required optional. Whether or not the feature is
	 *                           required. If this dependency is required,
	 *                           {@link SiteApplication} will vefiry the
	 *                           existance of the dependent feature.
	 */
	public function __construct($feature, $required = true)
	{
		$this->feature = (string)$feature;
		$this->required = (boolean)$required;
	}




	/**
	 * Gets the dependent feature of this dependency
	 *
	 * @return string the dependent feature of this dependency.
	 */
	public function getFeature()
	{
		return $this->feature;
	}




	/**
	 * Gets whether or not the dependent feature of this dependency is required
	 *
	 * If this dependency is required, {@link SiteApplication} will vefiry the
	 * existance of the dependent feature.
	 *
	 * @return boolean true if the dependent feature of this dependency
	 *                  is required and false if it is not.
	 */
	public function isRequired()
	{
		return $this->required;
	}


}

?>
