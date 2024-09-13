<?php

/**
 * Base class for edit pages.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class SiteEditPage extends SiteUiPage
{
    public const RELOCATE_URI_FIELD = '_relocate_uri';

    protected function isNew(SwatForm $form)
    {
        return true;
    }

    protected function getForms()
    {
        return $this->ui->getRoot()->getDescendants('SwatForm');
    }

    // process phase

    public function process()
    {
        foreach ($this->getForms() as $form) {
            $this->processForm($form);
        }
    }

    protected function processForm(SwatForm $form)
    {
        if ($this->authenticate($form)) {
            $form->process();

            if ($form->isProcessed()) {
                $this->validate($form);

                if ($this->isValid($form)) {
                    $this->save($form);
                    $this->relocate($form);
                }
            }
        }
    }

    /**
     * Authenticate a form on the page.
     */
    protected function authenticate(SwatForm $form)
    {
        $authenticated = true;

        if (!$form->isAuthenticated()) {
            $authenticated = false;

            if ($this->app->hasModule('SiteMessagesModule')) {
                $messages = $this->app->getModule('SiteMessagesModule');

                $message = new SwatMessage(
                    Site::_(
                        'There is a problem with the information submitted.'
                    ),
                    'warning'
                );

                $message->secondary_content = Site::_(
                    'In order to ensure your security, we were unable to ' .
                    'process your request. Please try again.'
                );

                $messages->add($message);
            }
        }

        return $authenticated;
    }

    protected function validate(SwatForm $form) {}

    protected function isValid(SwatForm $form)
    {
        $valid = true;

        if ($form->hasMessage()) {
            $valid = false;
        }

        foreach ($form->getChildren('SwatMessageDisplay') as $message_display) {
            if ($message_display->getMessageCount() > 0) {
                $valid = false;
                break;
            }
        }

        return $valid;
    }

    abstract protected function save(SwatForm $form);

    abstract protected function relocate(SwatForm $form);

    protected function relocateToRefererUri(SwatForm $form, $default_relocate)
    {
        $this->app->relocate($this->getRelocateUri($form, $default_relocate));
    }

    protected function assignUiValuesToObject(
        SwatDBDataObject $object,
        array $names
    ) {
        foreach ($names as $name) {
            $this->assignUiValueToObject($object, $name);
        }
    }

    protected function assignUiValueToObject(SwatDBDataObject $object, $name)
    {
        $widget = $this->ui->getWidget($name);
        // only clone the value when its actually an object
        if ($widget instanceof SwatDateEntry && $widget->value !== null) {
            $value = clone $widget->value;
            $value->setTZ($this->app->default_time_zone);
            $value->toUTC();
        } else {
            $value = $widget->value;
        }

        if (property_exists($object, $name)
            || $object->hasInternalValue($name)) {
            $object->{$name} = $value;
        } else {
            throw new SwatInvalidPropertyException(sprintf(
                'Specified “%s” object does not have a property “%s”.',
                $object::class,
                $name
            ));
        }
    }

    /**
     * Generates a shortname.
     *
     * This method allows edit pages to easily generate a unique shortname by
     * during their processing phase. The shortname is generated from the
     * provided text using {@link SwatString::condenseToName()} and then
     * validated with {@link SiteEditPage::validateShortname()}. If the initial
     * shortname is not valid, an integer is appended and incremented until the
     * shortname is valid. Subclasses should override
     * <code>validateShortname()</code> to perform whatever checks are
     * necessary to validate the shortname.
     *
     * @param string $text the text from which to generate the shortname
     *
     * @return string a shortname
     *
     * @see SiteEditPage::validateShortname()
     */
    protected function generateShortname($text)
    {
        $shortname_base = SwatString::condenseToName($text);
        $count = 1;
        $shortname = $shortname_base;

        while ($this->validateShortname($shortname) === false) {
            $shortname = $shortname_base . $count++;
        }

        return $shortname;
    }

    /**
     * Validates a shortname.
     *
     * This method is called by {@link SiteEditPage::generateShortname()} to
     * validate a generated shortname. By default, all shortnames are considered
     * valid. Subclasses should override this method to perform the necessary
     * checks to properly validate the shortname.
     *
     * @param string $shortname the shortname to validate
     *
     * @return bool true if the shortname is valid and false if not
     *
     * @see SiteEditPage::generateShortname()
     */
    protected function validateShortname($shortname)
    {
        return true;
    }

    protected function getRelocateUri(SwatForm $form, $default_relocate)
    {
        $uri = $form->getHiddenField(self::RELOCATE_URI_FIELD);

        if ($uri == '') {
            // backwards compatibility with old URL field
            $uri = $form->getHiddenField('_relocate_url');
        }

        if ($uri == '') {
            $uri = $default_relocate;
        }

        return $uri;
    }

    // build phase

    protected function buildMessages()
    {
        // parent builds app messages.
        parent::buildMessages();

        // build form messages.
        foreach ($this->getForms() as $form) {
            if ($form->isProcessed() && $form->hasMessage()) {
                $message_display = $this->getMessageDisplay($form);
                if ($message_display !== null) {
                    $message = $this->getInvalidMessage($form);
                    if ($message !== null) {
                        $message_display->add($message);
                    }
                }
            }
        }
    }

    protected function getMessageDisplay(?SwatForm $form = null)
    {
        return $form->getFirstDescendant('SwatMessageDisplay');
    }

    protected function getInvalidMessage(SwatForm $form)
    {
        $message = new SwatMessage(Site::_('There is a problem with ' .
            'the information submitted.'), 'error');

        $message->secondary_content = Site::_('Please address the ' .
            'fields highlighted below and re-submit the form.');

        return $message;
    }

    protected function buildInternal()
    {
        parent::buildInternal();
        foreach ($this->getForms() as $form) {
            $this->buildForm($form);
        }
    }

    protected function buildForm(SwatForm $form)
    {
        if ($this->source != '') {
            $form->action = $this->source;
        } else {
            $form->action = '.';
        }

        if (!$form->isProcessed() && !$this->isNew($form)) {
            $this->load($form);
        }

        if ($form->getHiddenField(self::RELOCATE_URI_FIELD) === null) {
            $uri = $this->getRefererUri();
            if ($uri !== null) {
                $form->addHiddenField(self::RELOCATE_URI_FIELD, $uri);
            }
        }
    }

    protected function load(SwatForm $form) {}

    protected function assignObjectValuesToUi(
        SwatDBDataObject $object,
        array $names
    ) {
        foreach ($names as $name) {
            $this->assignObjectValueToUi($object, $name);
        }
    }

    protected function assignObjectValueToUi(SwatDBDataObject $object, $name)
    {
        if (property_exists($object, $name)) {
            $value = $object->{$name};
        } elseif ($object->hasInternalValue($name)) {
            $value = $object->getInternalValue($name);
        } else {
            throw new SwatInvalidPropertyException(sprintf(
                'Specified “%s” record does not have a property “%s”.',
                $object::class,
                $name
            ));
        }

        $widget = $this->ui->getWidget($name);

        if ($value !== null && $widget instanceof SwatDateEntry) {
            $value = new SwatDate($value);
            $value->convertTZ($this->app->default_time_zone);
        }

        $widget->value = $value;
    }

    protected function getRefererUri()
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    // deprecated API

    /**
     * @deprecated use {@link SiteEditForm::relocateToRefererUri()} instead
     *
     * @param mixed $default_relocate
     */
    protected function relocateToRefererUrl(SwatForm $form, $default_relocate)
    {
        $this->relocateToRefererUri($form, $default_relocate);
    }

    /**
     * @deprecated use {@link SiteEditPage::getRefererUri()} instead
     */
    protected function getRefererUrl()
    {
        return $this->getRefererUri();
    }
}
