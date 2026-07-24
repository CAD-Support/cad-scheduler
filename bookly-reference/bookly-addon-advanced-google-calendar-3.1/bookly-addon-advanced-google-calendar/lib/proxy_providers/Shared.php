<?php
namespace BooklyAdvancedGoogleCalendar\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Google;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function doDailyRoutine()
    {
        if ( BooklyLib\Proxy\Pro::getGoogleCalendarSyncMode() == '2-way' ) {
            // Renew expired notification channels.
            $google   = new Google\Client();
            $deadline = BooklyLib\Slots\DatePoint::fromStr( '+3 days' )->value()->getTimestamp();
            foreach ( BooklyLib\Entities\Staff::query()->whereNot( 'google_data', null )->find() as $staff ) {
                if ( $google->auth( $staff ) && $google->data()->channel->id != '' ) {
                    if ( $google->data()->channel->expiration / 1000 < $deadline ) {
                        $google->calendar()->watch();
                    }
                }
            }
        }
    }
}