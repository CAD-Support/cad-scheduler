<?php
namespace BooklyAdvancedGoogleCalendar\Backend\Modules\Settings\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Settings\Proxy;
use BooklyAdvancedGoogleCalendar\Lib;
use BooklyPro\Lib\Google;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function saveSettings( array $alert, $tab, array $params )
    {
        if ( $tab == 'google_calendar' ) {
            update_option( 'bookly_gc_full_sync_offset_days_before', $params['bookly_gc_full_sync_offset_days_before'] );
            update_option( 'bookly_gc_full_sync_offset_days_after', $params['bookly_gc_full_sync_offset_days_after'] );
            update_option( 'bookly_gc_full_sync_titles', $params['bookly_gc_full_sync_titles'] );
            update_option( 'bookly_gc_force_update_description', $params['bookly_gc_force_update_description'] );

            $gc_client_id     = $params['bookly_gc_client_id'];
            $gc_client_secret = $params['bookly_gc_client_secret'];
            $gc_sync_mode     = $params['bookly_gc_sync_mode'];
            $google           = new Google\Client();
            if ( $gc_sync_mode == '2-way' && $gc_client_id != '' && $gc_client_secret != '' ) {
                foreach ( BooklyLib\Entities\Staff::query()->find() as $staff ) {
                    if ( $google->auth( $staff ) ) {
                        $google->calendar()->sync();
                        // Register new notification channels.
                        if ( ! $google->calendar()->watch() ) {
                            $alert['error'] = $google->getErrors();
                            break;
                        }
                    }
                }
            }
        }

        return $alert;
    }
}