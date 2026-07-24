<?php
namespace BooklyCustomerInformation\Backend\Modules\Customers\ProxyProviders;

use Bookly\Backend\Modules\Customers\Proxy;
use BooklyCustomerInformation\Lib;
use Bookly\Lib\Utils;

class Local extends Proxy\CustomerInformation
{
    /**
     * @inheritDoc
     */
    public static function prepareCustomerListData( array $customer_data, array $row )
    {
        $customer_data['info_fields'] = array();

        // Get data indexed by ID.
        $fields_data = array();
        foreach ( json_decode( $row['info_fields'] ) as $field_data ) {
            $fields_data[ $field_data->id ] = $field_data;
        }

        foreach ( Lib\ProxyProviders\Local::getFieldsWhichMayHaveData() as $field ) {
            if ( array_key_exists( $field->id, $fields_data ) ) {
                $value = $fields_data[ $field->id ]->value;
                if ( $value != '' ) {
                    switch ( $field->type ) {
                        case 'time':
                            $customer_data['info_fields'][ $field->id ] = Utils\DateTime::formatTime( $value );
                            break;
                        case 'date':
                            $customer_data['info_fields'][ $field->id ] = Utils\DateTime::formatDate( $value );
                            break;
                        default:
                            $customer_data['info_fields'][ $field->id ] = $value;
                    }
                } else {
                    $customer_data['info_fields'][ $field->id ] = '';
                }
            } else {
                $customer_data['info_fields'][ $field->id ] = $field->type == 'checkboxes' ? array() : '';
            }
        }

        return $customer_data;
    }
}