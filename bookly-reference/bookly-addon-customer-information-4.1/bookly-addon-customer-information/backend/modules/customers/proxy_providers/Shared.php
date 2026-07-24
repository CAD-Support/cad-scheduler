<?php
namespace BooklyCustomerInformation\Backend\Modules\Customers\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Customers\Proxy;
use BooklyCustomerInformation\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function mergeCustomers( $target_id, array $ids )
    {
        // Target.
        $target_customer = BooklyLib\Entities\Customer::find( $target_id );
        $target_fields   = array();
        foreach ( json_decode( $target_customer->getInfoFields() ) as $field ) {
            $target_fields[ $field->id ] = $field;
        }

        // Currently set up fields.
        $fields = array();
        foreach ( Lib\ProxyProviders\Local::getFieldsWhichMayHaveData() as $field ) {
            $fields[ $field->id ] = $field;
        }

        // Duplicates.
        foreach ( $ids as $id ) {
            if ( $id != $target_id ) {
                $customer = BooklyLib\Entities\Customer::find( $id );
                foreach ( json_decode( $customer->getInfoFields() ) as $field ) {
                    if (
                        ! isset ( $fields[ $field->id ] ) || (
                            isset ( $target_fields[ $field->id ] ) && (
                                ! empty ( $target_fields[ $field->id ]->value ) || $target_fields[ $field->id ]->value == '0'
                            )
                        )
                    ) {
                        continue;
                    }
                    $target_fields[ $field->id ] = $field;
                }
            }
        }

        // Update target customer.
        $target_customer->setInfoFields( json_encode( array_values( $target_fields ) ) );
    }
}