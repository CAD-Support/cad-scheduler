<?php
namespace BooklyCustomerInformation\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;
use BooklyCustomerInformation\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCalendarAppointmentCodes( array $codes, $participants )
    {
        if ( $participants == 'one' ) {
            $codes['info_fields'] = __( 'Combined values of all customer information fields', 'bookly' );
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( array $codes, $section )
    {
        switch ( $section ) {
            case 'woocommerce' :
            case 'calendar_one_participant' :
            case 'ics_for_customer':
                $codes['info_fields'] = array( 'description' => __( 'Combined values of all customer information fields', 'bookly' ) );
                foreach ( Lib\ProxyProviders\Local::getFieldsWhichMayHaveData() as $info_field ) {
                    $codes[ 'info_field#' . $info_field->id ] = array( 'description' => __( 'Customer information field', 'bookly' ) . ': ' . $info_field->label, 'if' => true );
                }
                break;
            case 'calendar_many_participants' :
            case 'google_calendar' :
            case 'outlook_calendar' :
            case 'ics_for_staff' :
                $ci_codes = array(
                    'info_fields' => array( 'description' => __( 'Combined values of all customer information fields', 'bookly' ) ),
                );
                foreach ( Lib\ProxyProviders\Local::getFieldsWhichMayHaveData() as $info_field ) {
                    $ci_codes[ 'info_field#' . $info_field->id ] = array( 'description' => __( 'Customer information field', 'bookly' ) . ': ' . $info_field->label, 'if' => true );
                }
                $codes = array_merge_recursive( $codes, array(
                    'participants' => array(
                        'loop' => array(
                            'codes' => $ci_codes,
                        ),
                    ),
                ) );
                break;
        }

        return $codes;
    }
}