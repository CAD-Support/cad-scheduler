<?php
namespace BooklyAdvancedGoogleCalendar\Backend\Components\Dialogs\Staff\Edit\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Staff\Edit\Proxy;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Google;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function updateStaffAdvanced( array $data, BooklyLib\Entities\Staff $staff, array $params )
    {
        if ( BooklyLib\Proxy\Pro::getGoogleCalendarSyncMode() === '2-way' ) {
            $google = new Google\Client();
            if ( $google->auth( $staff ) ) {
                $google->calendar()->sync();
                // Register new notification channel.
                if ( ! $google->calendar()->watch() ) {
                    $data['alerts']['error'][] = 'Google Calendar: ' . implode( '<br>', $google->getErrors() );
                }
            }
        }

        return $data;
    }
}