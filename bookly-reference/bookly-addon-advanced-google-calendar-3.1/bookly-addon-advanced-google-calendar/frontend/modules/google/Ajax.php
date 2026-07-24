<?php
namespace BooklyAdvancedGoogleCalendar\Frontend\Modules\Google;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Config;
use BooklyPro\Lib\Google;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Process PUSH notifications.
     */
    public static function pushNotifications()
    {
        if (
            ! Config::graceExpired() &&
            isset ( $_SERVER['HTTP_X_GOOG_RESOURCE_STATE'] ) &&
            $_SERVER['HTTP_X_GOOG_RESOURCE_STATE'] == 'exists' &&
            BooklyLib\Proxy\Pro::getGoogleCalendarSyncMode() == '2-way'
        ) {
            $channel_id = $_SERVER['HTTP_X_GOOG_CHANNEL_ID'];
            BooklyLib\Utils\Log::tempPut( BooklyLib\Utils\Log::OPTION_GOOGLE, null, null, $_SERVER['HTTP_X_GOOG_CHANNEL_ID'], 'Google push notification' );
            if ( preg_match( '/^bookly-(\d+)-.+/', $channel_id, $match ) ) {
                $staff = BooklyLib\Entities\Staff::find( $match[1] );
                if ( $staff ) {
                    $google = new Google\Client();
                    if ( $google->auth( $staff ) && $google->data()->channel->id == $channel_id ) {
                        $google->calendar()->sync();
                    }
                }
            }

            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * Override parent method to not check CSRF token.
     *
     * @param string $action
     * @return bool
     */
    protected static function csrfTokenValid( $action = null )
    {
        return true;
    }
}