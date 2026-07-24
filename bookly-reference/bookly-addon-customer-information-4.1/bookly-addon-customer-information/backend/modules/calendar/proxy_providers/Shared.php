<?php
namespace BooklyCustomerInformation\Backend\Modules\Calendar\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Calendar\Proxy;
use BooklyCustomerInformation\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareAppointmentCodesData( array $codes, $appointment_data, $participants )
    {
        $info_fields = Lib\ProxyProviders\Local::getFieldsWhichMayHaveData();
        if ( $participants == 'one' ) {
            if ( $appointment_data['customers'][0]['_info_fields'] ) {
                foreach ( $appointment_data['customers'][0]['_info_fields'] as $info_field ) {
                    $label = '';
                    foreach ( $info_fields as $field ) {
                        if ( $field->id == $info_field['id'] ) {
                            $label = $field->label;
                            break;
                        }
                    }
                    $value = nl2br( esc_html( is_array( $info_field['value'] ) ? implode( ',', $info_field['value'] ) : $info_field['value'] ) );
                    $codes['info_fields'] .= sprintf( '<div>%s: %s</div>', wp_strip_all_tags( $label ), $value );
                    $codes[ 'info_field#' . $info_field['id'] ] = $value;
                }
            }
        } else {
            foreach ( $appointment_data['customers'] as $id => $customer ) {
                if ( $customer['_info_fields'] ) {
                    foreach ( $customer['_info_fields'] as $info_field ) {
                        $label = '';
                        foreach ( $info_fields as $field ) {
                            if ( $field->id == $info_field['id'] ) {
                                $label = $field->label;
                                break;
                            }
                        }
                        $value = nl2br( esc_html( is_array( $info_field['value'] ) ? implode( ',', $info_field['value'] ) : $info_field['value'] ) );
                        $codes['participants'][ $id ]['info_fields'] .= sprintf( '<div>%s: %s</div>', wp_strip_all_tags( $label ), $value );
                        $codes['participants'][ $id ][ 'info_field#' . $info_field['id'] ] = $value;
                    }
                }
            }
        }

        return $codes;
    }
}