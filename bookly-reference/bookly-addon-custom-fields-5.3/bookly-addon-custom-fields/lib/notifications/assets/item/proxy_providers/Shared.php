<?php
namespace BooklyCustomFields\Lib\Notifications\Assets\Item\ProxyProviders;

use Bookly\Lib\Notifications\Assets\Item\Codes;
use Bookly\Lib\Notifications\Assets\Item\Proxy;
use Bookly\Lib\Utils;
use BooklyCustomFields\Lib\ProxyProviders\Local;

abstract class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCodes( Codes $codes )
    {
        if ( $codes->getItem()->isAppointment() ) {
            $codes->custom_fields = Local::getFormatted( $codes->getItem()->getCA(), 'text' );
            $codes->custom_fields_2c = Local::getFormatted( $codes->getItem()->getCA(), 'html' );
            $codes->custom_fields_data = array();
            $format_fields = Local::getOnly( array( 'date', 'time' ) ) ?: array();
            foreach ( json_decode( $codes->getItem()->getCA()->getCustomFields(), true ) ?: array() as $custom_field ) {
                $value = is_array( $custom_field['value'] ) ? implode( ',', $custom_field['value'] ) : $custom_field['value'];
                if ( $value != '' ) {
                    foreach ( $format_fields as $data ) {
                        if ( $data->id == $custom_field['id'] ) {
                            switch ( $data->type ) {
                                case 'date':
                                    $value = Utils\DateTime::formatDate( $custom_field['value'] );
                                    break;
                                case 'time':
                                    $value = Utils\DateTime::formatTime( $custom_field['value'] );
                                    break;
                            }
                        }
                    }
                }
                $codes->custom_fields_data[ 'custom_field#' . $custom_field['id'] ] = $value;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareReplaceCodes( array $replace_codes, Codes $codes, $format )
    {
        $replace_codes['custom_fields'] = $codes->custom_fields;
        $replace_codes['custom_fields_2c'] = $format === 'html' ? $codes->custom_fields_2c : $codes->custom_fields;
        if ( $codes->custom_fields_data !== null ) {
            foreach ( $codes->custom_fields_data as $key => $value ) {
                $replace_codes[ $key ] = $value;
            }
        }

        return $replace_codes;
    }
}