<?php

/**
 * Report page for Ad.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteAdIndex extends AdminIndex
{
    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->ui->loadFromXML($this->getUiXml());
    }

    protected function getUiXml()
    {
        return __DIR__ . '/index.xml';
    }

    // process phase

    protected function processActions(SwatView $view, SwatActions $actions)
    {
        $num = count($view->getSelection());

        switch ($actions->selected->id) {
            case 'delete':
                $this->app->replacePage('Ad/Delete');
                $this->app->getPage()->setItems($view->getSelection());
                break;
        }
    }

    // build phase

    protected function getTableModel(SwatView $view): SiteAdWrapper
    {
        $sql = sprintf(
            'select * from Ad
			order by %s',
            $this->getOrderByClause($view, 'createdate desc')
        );

        return SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(SiteAdWrapper::class)
        );
    }
}
