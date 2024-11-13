<?php

/**
 * Report page for Ads.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAdDetails extends AdminIndex
{
    /**
     * Maximum number of top http referers to display.
     */
    public const NUM_HTTP_REFERERS = 20;

    /**
     * @var SiteAd
     */
    protected $ad;

    /**
     * @var array
     */
    protected $periods;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $id = SiteApplication::initVar('id');
        if (!$this->initAd($id)) {
            throw new AdminNotFoundException(
                sprintf('Ad with an id of ‘%s’ not found.', $id)
            );
        }

        $this->ui->loadFromXML($this->getUiXml());
        $this->ui->getWidget('index_frame')->subtitle = $this->ad->title;

        $this->periods = ['day' => Site::_('Day'), 'week' => Site::_('Week'), 'two_week' => Site::_('2 Weeks'), 'month' => Site::_('Month'), 'total' => Site::_('Total')];
    }

    /**
     * @var int
     *
     * @param mixed $id
     *
     * @return bool
     */
    protected function initAd($id)
    {
        $class_name = SwatDBClassMap::get(SiteAd::class);
        $this->ad = new $class_name();
        $this->ad->setDatabase($this->app->db);

        return $this->ad->load($id);
    }

    protected function getUiXml()
    {
        return __DIR__ . '/details.xml';
    }

    // build phase

    protected function buildInternal()
    {
        parent::buildInternal();

        $toolbar = $this->ui->getWidget('details_toolbar');
        $toolbar->setToolLinkValues($this->ad->id);

        $this->buildHelp();
    }

    protected function buildHelp()
    {
        $inbound_tracking_id = $this->app->config->ads->tracking_id;

        $help_note = $this->ui->getWidget('ad_tag_help');
        $help_note->title = sprintf(
            Site::_(
                'To track this ad, append the variable “%s=%s” to incoming links.'
            ),
            SwatString::minimizeEntities($inbound_tracking_id),
            SwatString::minimizeEntities($this->ad->shortname)
        );

        ob_start();
        echo Site::_('Examples:'), '<ul>';

        $base_href = $this->app->getFrontendBaseHref();
        printf(
            '<li>%1$s<strong>?%2$s=%3$s</strong></li>' .
            '<li>%1$s?othervar=otherval<strong>&%2$s=%3$s</strong></li>' .
            '<li>%1$sus/en/category/product<strong>?%2$s=%3$s</strong></li>',
            SwatString::minimizeEntities($base_href),
            SwatString::minimizeEntities($inbound_tracking_id),
            SwatString::minimizeEntities($this->ad->shortname)
        );

        echo '</ul>';
        $help_note->content = ob_get_clean();
        $help_note->content_type = 'text/xml';
    }

    protected function getTableModel(SwatView $view): ?SwatTableModel
    {
        return match ($view->id) {
            'referrer_period_view' => $this->getReferrerPeriodTableModel(),
            'http_referers_view'   => $this->getHttpReferersTableModel(),
            default => null,
        };

    }

    protected function getReferrerPeriodTableModel(): SwatTableStore
    {
        $sql = sprintf(
            'select * from AdReferrerByPeriodView where ad = %s',
            $this->app->db->quote($this->ad->id, 'integer')
        );

        $row = SwatDB::queryRow($this->app->db, $sql);

        $store = new SwatTableStore();

        foreach ($this->periods as $key => $val) {
            $myvar->period = $val;
            $myvar->referrers = intval($row->{$key});

            $store->add(clone $myvar);
        }

        return $store;
    }

    protected function getHttpReferersTableModel(): SwatDBDefaultRecordsetWrapper
    {
        $sql = sprintf(
            'select http_referer as uri, count(id) as referer_count
				from AdReferrer
			where ad = %s and http_referer is not null
			group by ad, uri
			order by referer_count limit %s',
            $this->app->db->quote($this->ad->id, 'integer'),
            $this->app->db->quote(self::NUM_HTTP_REFERERS, 'integer')
        );

        return SwatDB::query($this->app->db, $sql);
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();
        $this->navbar->addEntry(new SwatNavBarEntry($this->ad->title));
    }
}
