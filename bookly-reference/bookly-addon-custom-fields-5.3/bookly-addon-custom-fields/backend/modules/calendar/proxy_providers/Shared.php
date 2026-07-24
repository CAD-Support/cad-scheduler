<?php
namespace BooklyCustomFields\Backend\Modules\Calendar\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Calendar\Proxy;
use BooklyCustomFields\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareAppointmentCodesData( array $codes, $appointment_data, $participants )
    {
        if ( $appointment_data['custom_fields'] !== '[]' ) {
            if ( $participants === 'one' ) {
                $ca = new BooklyLib\Entities\CustomerAppointment();
                $ca->setCustomFields( $appointment_data['custom_fields'] )
                    ->setAppointmentId( $appointment_data['id'] )
                    ->setId( $appointment_data['ca_id'] );
                foreach ( Lib\ProxyProviders\Local::getForCustomerAppointment( $ca ) as $custom_field ) {
                    $codes['custom_fields'] .= sprintf( '<div>%s: %s</div>', wp_strip_all_tags( $custom_field['label'] ), nl2br( esc_html( $custom_field['value'] ) ) );
                    $codes[ 'custom_field#' . $custom_field['id'] ] = nl2br( esc_html( is_array( $custom_field['value'] ) ? implode( ',', $custom_field['value'] ) : $custom_field['value'] ) );
                }
            } else {
                $custom_fields = Lib\ProxyProviders\Local::getWhichHaveData();
                foreach ( $appointment_data['customers'] as $id => $customer ) {
                    $readable_custom_fields = array();
                    if ( $customer['_custom_fields'] ) {
                        foreach ( $customer['_custom_fields'] as $custom_field ) {
                            $label = '';
                            foreach ( $custom_fields as $field ) {
                                if ( $field->id === $custom_field['id'] ) {
                                    $label = $field->label;
                                    break;
                                }
                            }
                            $value = nl2br( esc_html( is_array( $custom_field['value'] ) ? implode( ',', $custom_field['value'] ) : $custom_field['value'] ) );
                            $readable_custom_fields[] = sprintf( '<div>%s: %s</div>', wp_strip_all_tags( $label ), $value );
                            $codes['participants'][ $id ][ 'custom_field#' . $custom_field['id'] ] = $value;
                        }
                        $codes['participants'][ $id ]['custom_fields'] = implode( $readable_custom_fields );
                    }
                }
            }
        }

        return $codes;
    }
}