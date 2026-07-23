<?php
namespace BooklyCustomerInformation\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Proxy;
use BooklyCustomerInformation\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareInfoTextCodes( array $codes, array $data )
    {
        $codes['info_fields'] = isset( $data['info_fields'] ) ? implode( '<br>', $data['info_fields'] ) : '';
        foreach ( $data as $key => $value ) {
            if ( strpos( $key, 'info_field#' ) === 0 ) {
                $codes[ $key ] = $value;
            }
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCartItemInfoText( $data, BooklyLib\CartItem $cart_item, BooklyLib\UserBookingData $userData )
    {
        $info_fields = Lib\ProxyProviders\Local::getFieldsWhichMayHaveData();
        $data['info_fields'] = array();
        $fields = array();
        foreach ( $info_fields as $field ) {
            $fields[ $field->id ] = $field;
        }

        foreach ( $userData->getInfoFields() as $info_field ) {
            $label = '';
            if ( array_key_exists( $info_field['id'], $fields ) ) {
                $label = $fields[ $info_field['id'] ]->label;
            }

            $value = '';
            if ( array_key_exists( 'value', $info_field ) ) {
                $value = $info_field['value'];
                if ( $value && isset( $fields[ $info_field['id'] ] ) ) {
                    switch ( $fields[ $info_field['id'] ]->type ) {
                        case 'time':
                            $value = BooklyLib\Utils\DateTime::formatTime( $value );
                            break;
                        case 'date':
                            $value = BooklyLib\Utils\DateTime::formatDate( $value );
                            break;
                    }
                }
            } elseif ( property_exists( $fields[ $info_field['id'] ], 'default' ) ) {
                $value = $fields[ $info_field['id'] ]->default;
            }
            if ( is_array( $value ) ) {
                $value = implode( ',', $value );
            }

            $data['info_fields'][] = sprintf( '%s: %s', wp_strip_all_tags( $label ), $value );
            $data[ 'info_field#' . $info_field['id'] ] = $value;
        }

        return $data;
    }
}