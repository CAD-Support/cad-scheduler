<?php
namespace BooklyEvents\Backend\Modules\Notifications\ProxyProviders;

use Bookly\Backend\Modules\Notifications\Proxy;
use Bookly\Lib\Config;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareNotificationCodes( array $codes, $type )
    {
        if ( ! isset( $codes['event'] ) ) {
            $codes['event'] = array();
        }
        $codes['event']['event_title'] = array( 'description' => __( 'Title of event', 'bookly' ) );
        $codes['event']['event_info'] = array( 'description' => __( 'Info of event', 'bookly' ) );
        $codes['event']['event_start_date'] = array( 'description' => __( 'Start date of event', 'bookly' ) );
        $codes['event']['event_end_date'] = array( 'description' => __( 'End date of event', 'bookly' ) );
        $codes['event']['event_start_time'] = array( 'description' => __( 'Start time of event', 'bookly' ) );
        $codes['event']['event_end_time'] = array( 'description' => __( 'End time of event', 'bookly' ) );
        $codes['event']['event_image_url'] = array( 'description' => __( 'Image URL of event', 'bookly' ), 'if' => true );
        $codes['event']['event_image'] = array( 'description' => __( 'Image of event', 'bookly' ), 'if' => true );
        $codes['event']['online_meeting_join_url'] = array( 'description' => __( 'Online meeting join URL', 'bookly' ) );
        $codes['event']['online_meeting_password'] = array( 'description' => __( 'Online meeting password', 'bookly' ) );
        $codes['event']['online_meeting_start_url'] = array( 'description' => __( 'Online meeting start URL', 'bookly' ) );
        $codes['event']['online_meeting_url'] = array( 'description' => __( 'Online meeting URL', 'bookly' ) );

        $codes['attendee'] = array(
            'attendee_code' => array( 'description' => __( 'Ticket code of attendee', 'bookly' ) ),
            'attendee_email' => array( 'description' => __( 'Email of attendee', 'bookly' ) ),
            'attendee_phone' => array( 'description' => __( 'Phone of attendee', 'bookly' ) ),
            'attendee_first_name' => array( 'description' => __( 'First name of attendee', 'bookly' ) ),
            'attendee_last_name' => array( 'description' => __( 'Last name of attendee', 'bookly' ) ),
            'attendee_full_name' => array( 'description' => __( 'Full name of attendee', 'bookly' ) ),
            'ticket_name' => array( 'description' => __( 'Name of ticket type', 'bookly' ) ),
            'ticket_price' => array( 'description' => __( 'Price of ticket type', 'bookly' ) ),
        );

        $_codes_attendee = $codes['attendee'];

        $_codes_attendee['ticket_quantity'] = array( 'description' => __( 'Quantity of ticket type', 'bookly' ) );
        $_codes_attendee['ticket_reserved'] = array( 'description' => __( 'Reserved of ticket type', 'bookly' ) );

        if ( Config::locationsActive() ) {
            $_codes_attendee['location_info'] = array( 'description' => __( 'Location info', 'bookly' ), 'if' => true );
            $_codes_attendee['location_name'] = array( 'description' => __( 'Location name', 'bookly' ) );
        }

        $codes['attendees_list'] = array(
            'attendees_count' => array( 'description' => __( 'Number of attendees', 'bookly' ) ),
            'attendees' => array(
                'description' => array(
                    __( 'Loop over attendees list', 'bookly' ),
                    __( 'Loop over attendees list with delimiter', 'bookly' ),
                ),
                'loop' => array(
                    'item' => 'attendee',
                    'codes' => array_merge(
                        $codes['event'],
                        $_codes_attendee
                    ),
                ),
            ),
        );

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function buildNotificationCodesList( array $codes, $notification_type, array $codes_data )
    {
        switch ( $notification_type ) {
            case 'new_event':
                $codes = array_merge(
                    $codes_data['company'],
                    $codes_data['event']
                );
                break;
            case 'new_attendees':
                $codes = array_merge(
                    $codes_data['company'],
                    $codes_data['attendees_list'],
                    $codes_data['payment'],
                    $codes_data['order']
                );
                break;
            case 'attendee_deleted':
                $codes = array_merge(
                    $codes_data['company'],
                    $codes_data['event'],
                    $codes_data['attendee'],
                    $codes_data['customer'],
                    $codes_data['order']
                );
        }

        return $codes;
    }
}