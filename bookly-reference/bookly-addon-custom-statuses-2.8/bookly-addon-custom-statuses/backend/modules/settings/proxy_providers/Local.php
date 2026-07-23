<?php
namespace BooklyCustomStatuses\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Lib as BooklyLib;

class Local extends Proxy\CustomStatuses
{
    /**
     * @inheritDoc
     */
    public static function renderTab()
    {
        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::CUSTOM_STATUSES );

        self::renderTemplate( 'settings_tab', compact( 'datatables' ) );
    }
}