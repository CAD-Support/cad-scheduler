<?php
namespace BooklyCustomFields\Backend\Modules\Notifications\ProxyProviders;

use Bookly\Backend\Modules\Notifications\Proxy;
use BooklyCustomFields\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareNotificationCodes( array $codes, $type )
    {
        $codes['customer_appointment']['custom_fields'] = array( 'description' => __( 'Combined values of all custom fields', 'bookly' ) );
        $codes['appointments_list']['appointments']['loop']['codes']['custom_fields'] = array( 'description' => __( 'Combined values of all custom fields', 'bookly' ) );
        foreach ( Lib\ProxyProviders\Local::getAll( array( 'captcha', 'text-content', 'file' ) ) as $custom_field ) {
            $codes['customer_appointment'][ 'custom_field#' . $custom_field->id ] = array( 'description' => __( 'Custom field', 'bookly' ) . ': ' . $custom_field->label, 'if' => true );
            $codes['appointments_list']['appointments']['loop']['codes'][ 'custom_field#' . $custom_field->id ] = array( 'description' => __( 'Custom field', 'bookly' ) . ': ' . $custom_field->label, 'if' => true );
        }
        $codes['staff_agenda']['next_day_agenda_extended'] = array( 'description' => __( 'Extended staff agenda for next day', 'bookly' ) );
        if ( $type === 'email' ) {
            $codes['customer_appointment']['custom_fields_2c'] = array( 'description' => __( 'Combined values of all custom fields (formatted in 2 columns)', 'bookly' ) );
        }

        return $codes;
    }
}