<?php
namespace BooklyCustomFields\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;
use BooklyCustomFields\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCalendarAppointmentCodes( array $codes, $participants )
    {
        if ( $participants == 'one' ) {
            $codes['custom_fields'] = __( 'Combined values of all custom fields', 'bookly' );
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( array $codes, $section )
    {
        switch ( $section ) {
            case 'woocommerce':
            case 'calendar_one_participant':
            case 'ics_for_customer':
                $codes['custom_fields'] = array( 'description' => __( 'Combined values of all custom fields', 'bookly' ) );
                foreach ( Lib\ProxyProviders\Local::getAll( array( 'captcha', 'text-content', 'file' ) ) as $custom_field ) {
                    $codes[ 'custom_field#' . $custom_field->id ] = array( 'description' => __( 'Custom field', 'bookly' ) . ': ' . $custom_field->label, 'if' => true );
                }
                break;
            case 'google_calendar':
            case 'outlook_calendar':
            case 'apple_calendar':
            case 'calendar_many_participants':
            case 'ics_for_staff':
                $cf_codes = array(
                    'custom_fields' => array( 'description' => __( 'Combined values of all custom fields', 'bookly' ) ),
                );
                foreach ( Lib\ProxyProviders\Local::getAll( array( 'captcha', 'text-content', 'file' ) ) as $custom_field ) {
                    $cf_codes[ 'custom_field#' . $custom_field->id ] = array( 'description' => __( 'Custom field', 'bookly' ) . ': ' . $custom_field->label, 'if' => true );
                }
                $codes = array_merge_recursive( $codes, array(
                    'participants' => array(
                        'loop' => array(
                            'codes' => $cf_codes,
                        ),
                    ),
                ) );
                break;
        }

        return $codes;
    }
}