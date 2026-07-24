<?php
namespace BooklyAdvancedGoogleCalendar\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Google;

class Local extends Proxy\AdvancedGoogleCalendar
{
    /**
     * @inheritDoc
     */
    public static function preSaveSettings( array $alert, array $params )
    {
        $gc_client_id     = $params['bookly_gc_client_id'];
        $gc_client_secret = $params['bookly_gc_client_secret'];
        $gc_sync_mode     = $params['bookly_gc_sync_mode'];
        $google           = new Google\Client();
        if (
            $gc_client_id != get_option( 'bookly_gc_client_id' ) ||
            $gc_client_secret != get_option( 'bookly_gc_client_secret' ) ||
            $gc_sync_mode != '2-way'
        ) {
            // Clean up channels.
            foreach ( BooklyLib\Entities\Staff::query()->whereNot( 'google_data', null )->find() as $staff ) {
                if ( $google->auth( $staff ) ) {
                    $google->calendar()->clearSyncToken()->stopWatching();
                }
            }
        }

        return $alert;
    }

    /**
     * @inheritDoc
     */
    public static function renderSettings()
    {
        self::renderTemplate( 'settings' );
    }
}