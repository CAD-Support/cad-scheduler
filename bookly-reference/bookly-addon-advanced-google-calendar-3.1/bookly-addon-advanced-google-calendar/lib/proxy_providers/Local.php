<?php
namespace BooklyAdvancedGoogleCalendar\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyAdvancedGoogleCalendar\Lib;
use BooklyPro\Lib\Google;

class Local extends BooklyLib\Proxy\AdvancedGoogleCalendar
{
    /**
     * @inheritDoc
     */
    public static function createApiCalendar( Google\Client $client )
    {
        return new Lib\Google\Calendar( $client );
    }

    /**
     * @inheritDoc
     */
    public static function reSync()
    {
        if ( BooklyLib\Proxy\Pro::getGoogleCalendarSyncMode() === '2-way' ) {
            // Re-sync calendars.
            $google = new Google\Client();
            foreach ( BooklyLib\Entities\Staff::query()->whereNot( 'visibility', 'archive' )->find() as $staff ) {
                if ( $google->auth( $staff ) && $google->calendar()->clearSyncToken()->sync() ) {
                    $google->calendar()->watch();
                }
            }
        }
    }
}