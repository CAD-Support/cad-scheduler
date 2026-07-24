<?php
namespace BooklyAdvancedGoogleCalendar\Backend\Modules\Calendar;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Google;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => array( 'staff', 'supervisor' ) );
    }

    /**
     * Run incremental synchronization with Google Calendar.
     */
    public static function sync()
    {
        if ( BooklyLib\Proxy\Pro::getGoogleCalendarSyncMode() === '2-way' ) {
            $staff_members = BooklyLib\Utils\Common::isCurrentUserAdmin()
                ? BooklyLib\Entities\Staff::query()->whereNot( 'google_data', null )->find()
                : BooklyLib\Entities\Staff::query()->where( 'wp_user_id', get_current_user_id() )->find();
            $google = new Google\Client();

            foreach ( $staff_members as $staff ) {
                if ( $google->auth( $staff ) ) {
                    if ( ! $google->calendar()->sync() ) {
                        wp_send_json_error( array( 'alert' => array( 'error' => $google->getErrors() ) ) );
                    }
                }
            }

            wp_send_json_success( array( 'alert' => array( 'success' => array( __( 'Calendars synchronized successfully.', 'bookly' ) ) ) ) );
        }

        wp_send_json_error();
    }
}