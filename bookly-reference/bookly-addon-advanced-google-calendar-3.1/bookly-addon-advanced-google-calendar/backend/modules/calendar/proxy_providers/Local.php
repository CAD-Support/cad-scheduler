<?php
namespace BooklyAdvancedGoogleCalendar\Backend\Modules\Calendar\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Calendar\Proxy;

class Local extends Proxy\AdvancedGoogleCalendar
{
    /**
     * @inheritDoc
     */
    public static function renderSyncButton( array $staff_members )
    {
        $show_sync_button = false;
        if ( BooklyLib\Proxy\Pro::getGoogleCalendarSyncMode() == '2-way' ) {
            foreach ( $staff_members as $staff ) {
                if ( $staff->getGoogleData() != '' ) {
                    $show_sync_button = true;
                    break;
                }
            }
        }

        self::renderTemplate( 'sync_button', compact( 'show_sync_button' ) );
    }
}