<?php
namespace BooklyCustomerInformation\Backend\Modules\Notifications\ProxyProviders;

use Bookly\Backend\Modules\Notifications\Proxy;
use BooklyCustomerInformation\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareNotificationCodes( array $codes, $type )
    {
        $codes['customer_appointment']['info_fields'] = array( 'description' => __( 'Combined values of all customer information fields', 'bookly' ) );
        foreach ( Lib\ProxyProviders\Local::getFieldsWhichMayHaveData() as $info_field ) {
            $codes['customer_appointment'][ 'info_field#' . $info_field->id ] = array( 'description' => __( 'Customer information field', 'bookly' ) . ': ' . $info_field->label, 'if' => true );
        }

        return $codes;
    }
}