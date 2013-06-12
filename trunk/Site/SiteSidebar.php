<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatWidget.php';
require_once 'Swat/SwatUIParent.php';
require_once 'SwatDB/SwatDBReadaheadIterator.php';
require_once 'Site/gadgets/SiteGadget.php';

/**
 * Sidebar widget that can contain multiple gadgets
 *
 * Each displayed gadget is wrapped in a containing div element with the CSS
 * class 'site-sidebar-gadget'.
 *
 * The first gadget will also have the special CSS class
 * 'site-sidebar-gadget-first' applied. Similarly, the last gadget will also
 * have the special CSS class 'site-sidebar-gadget-last' applied.
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteGadget
 */
class SiteSidebar extends SwatWidget implements SwatUIParent
{
	// {{{ protected properties

	/**
	 * Gadgets in this sidebar
	 *
	 * An array containing the gadgets that belong to this sidebar.
	 *
	 * @var array
	 */
	protected $gadgets = array();

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this sidebar
	 *
	 * Initializes contained gadgets.
	 *
	 * @see SwatWidget::init()
	 */
	public function init()
	{
		parent::init();

		foreach($this->gadgets as $gadget) {
			$gadget->init();
		}
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this sidebar by calling process() on all gadgets
	 */
	public function process()
	{
		parent::process();

		foreach ($this->gadgets as $gadget) {
			$gadget->process();
		}
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this sidebar by calling display() on all contained gadgets
	 *
	 * Each gadget is wrapped in a containing div element with the CSS class
	 * 'site-sidebar-gadget'.
	 *
	 * The first gadget will have the special CSS class
	 * 'site-sidebar-gadget-first' applied. Similarly, the last gadget will
	 * have the special CSS class 'site-sidebar-gadget-last' applied.
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'site-sidebar';
		$div_tag->open();

		$first = reset($this->gadgets);
		$iterator = new SwatDBReadaheadIterator($this->gadgets);
		while ($iterator->iterate()) {
			$gadget = $iterator->getCurrent();

			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'site-sidebar-gadget';

			if ($first === $gadget)
				$div_tag->class.= ' site-sidebar-gadget-first';

			if ($iterator->isLast())
				$div_tag->class.= ' site-sidebar-gadget-last';

			$div_tag->class.= $this->getGadgetCSSClassName($gadget);

			$div_tag->open();

			$gadget->display();

			$div_tag->close();
		}

		$div_tag->close();
	}

	// }}}
	// {{{ public function getMessages()

	/**
	 * Gets all messages
	 *
	 * @return array an array of gathered {@link SwatMessage} objects.
	 *
	 * @see SwatWidget::getMessages()
	 */
	public function getMessages()
	{
		$messages = parent::getMessages();

		foreach ($this->gadgets as $gadget)
			$messages = array_merge($messages, $gadget->getMessages());

		return $messages;
	}

	// }}}
	// {{{ public function hasMessage()

	/**
	 * Checks for the presence of messages
	 *
	 * @return boolean true if this sidebar or the subtree below this sidebar
	 *                  has one or more messages.
	 *
	 * @see SwatWidget::hasMessages()
	 */
	public function hasMessage()
	{
		$has_message = parent::hasMessage();

		if (!$has_message) {
			foreach ($this->gadgets as $gadget) {
				if ($gadget->hasMessage()) {
					$has_message = true;
					break;
				}
			}
		}

		return $has_message;
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	/**
	 * Gets the SwatHtmlHeadEntry objects needed by this sidebar
	 *
	 * @return SwatHtmlHeadEntrySet the SwatHtmlHeadEntry objects needed by
	 *                               this sidebar.
	 *
	 * @see SwatUIObject::getHtmlHeadEntrySet()
	 */
	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();

		foreach ($this->gadgets as $gadget)
			$set->addEntrySet($gadget->getHtmlHeadEntrySet());

		return $set;
	}

	// }}}
	// {{{ public function getFirst()

	/**
	 * Gets the first gadget in this sidebar
	 *
	 * @return SiteGadget the first gadget or null if there are no gadgets.
	 */
	public function getFirst()
	{
		$first = null;
		if (count($this->gadgets) > 0) {
			$first = reset($this->gadgets);
		}
		return $first;
	}

	// }}}
	// {{{ public function add()

	/**
	 * Adds a gadget to this sidebar
	 *
	 * @param SiteGadget $gadget the gadget to add.
	 */
	public function add(SiteGadget $gadget)
	{
		$this->gadgets[] = $gadget;
	}

	// }}}
	// {{{ public function addChild()

	/**
	 * Add a child object to this object
	 *
	 * @param SiteGadget $child the reference to the child object to add.
	 *
	 * @throws SwatInvalidClassException if the given <i>$child</i> is not an
	 *                                    instance of {@link SiteGadget}.
	 *
	 * @see SwatUIParent::addChild()
	 */
	public function addChild(SwatObject $child)
	{
		if (!($child instanceof SiteGadget))
			throw new SwatInvalidClassException(
				'Only SiteGadget objects may be nested within '.
				get_class($this).' objects.', 0, $child);

		$this->add($child);
	}

	// }}}
	// {{{ public function getGadgets()

	/**
	 * Gets all gadgets in this sidebar
	 *
	 * Retrieves an array of all gadgets directly contained by this sidebar.
	 *
	 * @param string $class_name optional class name. If set, only gadgets that
	 *                            are instances of <i>$class_name</i> are
	 *                            returned.
	 *
	 * @return array the gadgets of this sidebar,
	 */
	public function getGadtets($class_name = null)
	{
		if ($class_name === null)
			return $this->gadgets;

		$out = array();

		foreach($this->gadgets as $gadget)
			if ($gadget instanceof $class_name)
				$out[] = $gadget;

		return $out;
	}

	// }}}
	// {{{ public function getDescendants()

	/**
	 * Gets descendant UI-objects
	 *
	 * @param string $class_name optional class name. If set, only UI-objects
	 *                            that are instances of <i>$class_name</i> are
	 *                            returned.
	 *
	 * @return array the descendant UI-objects of this container. If descendant
	 *                objects have identifiers, the identifier is used as the
	 *                array key.
	 *
	 * @see SwatUIParent::getDescendants()
	 */
	public function getDescendants($class_name = null)
	{
		if (!($class_name === null ||
			class_exists($class_name) || interface_exists($class_name)))
			return array();

		$out = array();

		foreach ($this->gadgets as $gadget) {
			if ($class_name === null || $gadget instanceof $class_name) {
				$out[] = $gadget;
			}

			if ($gadget instanceof SwatUIParent) {
				$out = array_merge($out, $gadget->getDescendants($class_name));
			}
		}

		return $out;
	}

	// }}}
	// {{{ public function getFirstDescendant()

	/**
	 * Gets the first descendant UI-object of a specific class
	 *
	 * @param string $class_name class name to look for.
	 *
	 * @return SwatUIObject the first descendant UI-object or null if no
	 *                       matching descendant is found.
	 *
	 * @see SwatUIParent::getFirstDescendant()
	 */
	public function getFirstDescendant($class_name)
	{
		if (!class_exists($class_name) && !interface_exists($class_name))
			return null;

		$out = null;

		foreach ($this->gadgets as $gadget) {
			if ($gadget instanceof $class_name) {
				$out = $gadget;
				break;
			}

			if ($gadget instanceof SwatUIParent) {
				$out = $gadget->getFirstDescendant($class_name);
				if ($out !== null)
					break;
			}
		}

		return $out;
	}

	// }}}
	// {{{ public function getDescendantStates()

	/**
	 * Gets descendant states
	 *
	 * Retrieves an array of states of all stateful UI-objects in the widget
	 * subtree below this sidebar.
	 *
	 * @return array an array of UI-object states with UI-object identifiers as
	 *                array keys.
	 */
	public function getDescendantStates()
	{
		$states = array();

		foreach ($this->getDescendants('SwatState') as $id => $object)
			$states[$id] = $object->getState();

		return $states;
	}

	// }}}
	// {{{ public function setDescendantStates()

	/**
	 * Sets descendant states
	 *
	 * Sets states on all stateful UI-objects in the widget subtree below this
	 * sidebar.
	 *
	 * @param array $states an array of UI-object states with UI-object
	 *                       identifiers as array keys.
	 */
	public function setDescendantStates(array $states)
	{
		foreach ($this->getDescendants('SwatState') as $id => $object)
			if (isset($states[$id]))
				$object->setState($states[$id]);
	}

	// }}}
	// {{{ public function printWidgetTree()

	public function printWidgetTree()
	{
		echo get_class($this), ' ', $this->id;

		if (count($this->gadgets) > 0) {
			echo '<ul>';
			foreach ($this->gadgets as $gadget) {
				echo '<li>', get_class($gadget), '</li>';
			}
			echo '</ul>';
		}
	}

	// }}}
	// {{{ protected function getGadgetCSSClassName()

	/**
	 * @param SiteGadget $gadget
	 *
	 * @return string
	 */
	protected function getGadgetCSSClassName(SiteGadget $gadget)
	{
		$php_class_name = get_class($gadget);
		$css_class_names = array();

		// get the ancestors that are swat classes
		while (strcmp($php_class_name, 'SiteGadget') !== 0) {
			$css_class_name = strtolower(preg_replace('/([A-Z])/u',
				'-\1', $php_class_name));

			if (substr($css_class_name, 0, 1) === '-')
				$css_class_name = substr($css_class_name, 1);

			array_unshift($css_class_names, $css_class_name);
			$php_class_name = get_parent_class($php_class_name);
		}

		$css_class_name = implode(' ', $css_class_names);
		if (count($css_class_names) > 0) {
			$css_class_name = ' '.$css_class_name;
		}

		return $css_class_name;
	}

	// }}}
}

?>
