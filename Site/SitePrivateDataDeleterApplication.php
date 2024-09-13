<?php

/**
 * Framework for a command line application to remove personal data.
 *
 * @copyright 2006-2016 silverorange
 *
 * @todo      Cleanup instance code to make it more optional.
 */
class SitePrivateDataDeleterApplication extends SiteCommandLineApplication
{
    /**
     * A convenience reference to the database object.
     *
     * @var MDB2_Driver
     */
    public $db;

    /**
     * @var array of SitePrivateDataDeleter
     */
    protected $deleters = [];

    /**
     * @var bool
     */
    protected $dry_run = false;

    /**
     * Creates a new private data deleter application.
     *
     * @param string $id
     * @param string $config_filename
     * @param string $title
     * @param string $documentation
     *
     * @see SiteCommandLineApplication::__construct()
     */
    public function __construct($id, $config_filename, $title, $documentation)
    {
        parent::__construct($id, $config_filename, $title, $documentation);

        $instance = new SiteCommandLineArgument(
            ['-i', '--instance'],
            'setInstance',
            'Optional. Sets the site instance for which to ' .
            'run this application.'
        );

        $instance->addParameter(
            'string',
            'instance name must be specified.'
        );

        $this->addCommandLineArgument($instance);

        $debug = new SiteCommandLineArgument(
            ['-D', '--debug'],
            'setDebug',
            Site::_('Turns on debugging mode which causes ' .
            'output for each action to be sent to stdout.')
        );

        $dry_run = new SiteCommandLineArgument(
            ['--dry-run'],
            'setDryRun',
            Site::_('No private data is actually deleted. Use ' .
            'with --debug to see what data will be deleted.')
        );

        $this->addCommandLineArgument($debug);
        $this->addCommandLineArgument($dry_run);
    }

    public function setInstance($shortname)
    {
        putenv(sprintf('instance=%s', $shortname));
        $this->instance->init();
        $this->config->init();
    }

    public function setDebug($debug)
    {
        $verbosity = ($debug) ?
            SiteCommandLineApplication::VERBOSITY_ALL :
            SiteCommandLineApplication::VERBOSITY_NONE;

        $this->setVerbosity($verbosity);
    }

    public function setDryRun($dry_run)
    {
        $this->dry_run = (bool) $dry_run;
    }

    public function isDryRun()
    {
        return $this->dry_run;
    }

    public function run()
    {
        $this->initModules();
        $this->parseCommandLineArguments();

        if ($this->dry_run) {
            $this->debug(
                Site::_("Dry Run. No data will actually be deleted.\n")
            );
        }

        foreach ($this->deleters as $deleter) {
            $deleter->run();
        }

        if ($this->dry_run) {
            $this->debug(
                Site::_("\nDry Run. No data was actually deleted.\n\n")
            );
        }
    }

    public function addDeleter(SitePrivateDataDeleter $deleter)
    {
        $deleter->app = $this;
        $this->deleters[] = $deleter;
    }

    /**
     * Displays debug output.
     *
     * This method is made public so individual deleters can use it.
     *
     * @param string $string the string to display
     * @param bool   $bold   optional. Whether or not to display the string
     *                       using a bold font on supported terminals. Defaults
     *                       to false.
     */
    public function debug($string, $bold = false)
    {
        parent::debug($string, $bold);
    }

    // boilerplate

    /**
     * Gets the list of modules to load for this search indexer.
     *
     * @return array the list of modules to load for this application
     *
     * @see SiteApplication::getDefaultModuleList()
     */
    protected function getDefaultModuleList()
    {
        return array_merge(
            parent::getDefaultModuleList(),
            [
                'config'   => SiteCommandLineConfigModule::class,
                'database' => SiteDatabaseModule::class,
                'instance' => SiteMultipleInstanceModule::class,
            ]
        );
    }

    /**
     * Configures modules of this application before they are initialized.
     *
     * @param SiteConfigModule $config the config module of this application to
     *                                 use for configuration other modules
     */
    protected function configure(SiteConfigModule $config)
    {
        parent::configure($config);
        $this->database->dsn = $config->database->dsn;
    }

    /**
     * Initializes the modules of this application and sets up the database
     * convenience reference.
     */
    public function initModules()
    {
        parent::initModules();
        $this->db->loadModule('Datatype', null, true);
    }
}
