<?php
namespace BooklyCustomerInformation\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Proxy\CustomerInformation as CustomerInformationProxy;
use BooklyCustomerInformation\Lib;

class Local extends CustomerInformationProxy
{
    /**
     * @inheritDoc
     */
    public static function renderDetailsStep( BooklyLib\UserBookingData $userData )
    {
        $fields = array();
        foreach ( Lib\ProxyProviders\Local::getTranslatedFields() as $field ) {
            if ( $field->visible ) {
                $fields[] = $field;
            }
        }

        $data = array();
        foreach ( $userData->getInfoFields() as $field ) {
            $data[ $field['id'] ] = $field['value'];
        }

        foreach ( $fields as $field ) {
            if ( ! isset( $data[ $field->id ] ) && property_exists( $field, 'default' ) ) {
                $data[ $field->id ] = $field->default;
            }
        }

        $is_logged_in = $userData->getCustomer() && get_current_user_id() > 0 && $userData->getCustomer()->getWpUserId() == get_current_user_id();
        $form_id = $userData->getFormId();

        self::renderTemplate( '6_details', compact( 'fields', 'data', 'is_logged_in', 'form_id' ) );
    }
}