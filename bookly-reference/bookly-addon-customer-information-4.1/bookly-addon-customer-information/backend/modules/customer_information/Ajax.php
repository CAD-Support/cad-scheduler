<?php
namespace BooklyCustomerInformation\Backend\Modules\CustomerInformation;

use Bookly\Lib as BooklyLib;
use BooklyCustomerInformation\Lib\Utils\Common;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Save customer fields.
     */
    public static function saveFields()
    {
        $fields = self::parameter( 'fields', array() );

        foreach ( $fields as &$info_field ) {
            $info_field = (object) $info_field;
            Common::registerTranslationInfoField( $info_field );
        }

        update_option( 'bookly_customer_information_data', json_encode( $fields ) );

        wp_send_json_success();
    }
}