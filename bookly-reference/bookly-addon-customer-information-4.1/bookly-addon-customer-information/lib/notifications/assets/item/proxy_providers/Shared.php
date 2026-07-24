<?php
namespace BooklyCustomerInformation\Lib\Notifications\Assets\Item\ProxyProviders;

use Bookly\Lib\Entities\Customer;
use Bookly\Lib\Notifications\Assets\Item\Codes;
use Bookly\Lib\Notifications\Assets\Item\Proxy;
use BooklyCustomerInformation\Lib\ProxyProviders\Local;
use Bookly\Lib\Utils;

abstract class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCodes( Codes $codes )
    {
        $codes->info_fields = '';
        $codes->info_fields_data = array();

        if ( $codes->getItem()->isAppointment() ) {
            $customer = Customer::find( $codes->getItem()->getCA()->getCustomerId() );
            if ( $customer ) {
                $fields = Local::getFieldsWhichMayHaveData() ?: array();
                foreach ( (array) json_decode( $customer->getInfoFields(), true ) as $info_field ) {
                    $value = is_array( $info_field['value'] ) ? implode( ',', $info_field['value'] ) : $info_field['value'];
                    $label = '';
                    foreach ( $fields as $data ) {
                        if ( $data->id == $info_field['id'] ) {
                            $label = $data->label;
                            if ( $value != '' ) {
                                switch ( $data->type ) {
                                    case 'date':
                                        $value = Utils\DateTime::formatDate( $info_field['value'] );
                                        break;
                                    case 'time':
                                        $value = Utils\DateTime::formatTime( $info_field['value'] );
                                        break;
                                }
                            }
                        }
                    }
                    $codes->info_fields_data[ 'info_field#' . $info_field['id'] ] = $value;
                    $codes->info_fields .= sprintf( '<div>%s: %s</div>', wp_strip_all_tags( $label ), $value );
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareReplaceCodes( array $replace_codes, Codes $codes, $format )
    {
        $replace_codes['info_fields'] = $codes->info_fields;
        if ( $codes->info_fields_data !== null ) {
            foreach ( $codes->info_fields_data as $key => $value ) {
                $replace_codes[ $key ] = $value;
            }
        }

        return $replace_codes;
    }
}