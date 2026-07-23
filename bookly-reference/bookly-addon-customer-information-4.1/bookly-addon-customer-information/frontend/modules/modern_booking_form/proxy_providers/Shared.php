<?php
namespace BooklyCustomerInformation\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib\Entities\Customer;
use BooklyCustomerInformation\Lib;
use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\Request;

class Shared extends \Bookly\Frontend\Modules\ModernBookingForm\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareFormOptions( $bookly_options )
    {
        $bookly_options['time_slot_length'] = (int) get_option( 'bookly_gen_time_slot_length' );

        // Get saved customer information
        $customer_information = array();

        if ( $bookly_options['customer']['id'] ) {
            $ci_data = json_decode( Customer::query()->where( 'id', $bookly_options['customer']['id'] )->fetchVar( 'info_fields' ), true ) ?: array();
        } else {
            $ci_data = isset( $_COOKIE['bookly-customer-info-fields'] )
                ? json_decode( stripslashes( $_COOKIE['bookly-customer-info-fields'] ), true )
                : array();
        }

        $exist_fields = array();
        foreach ( Lib\ProxyProviders\Local::getFieldsWhichMayHaveData() as $field ) {
            $exist_fields[] = $field->id;
        }

        // Filter deprecated fields
        foreach ( $ci_data ?: array() as $ci ) {
            isset( $ci['value'] ) && in_array( $ci['id'], $exist_fields ) && $customer_information[ $ci['id'] ] = $ci['value'];
        }

        $bookly_options['customer']['customer_information'] = (object) $customer_information;
        $bookly_options['customer_information'] = array();

        foreach ( Lib\ProxyProviders\Local::getTranslatedFields() as $field ) {
            if ( $field->visible && ! ( property_exists( $field, 'ask_once' ) && $field->ask_once && isset( $customer_information[ $field->id ] ) ) ) {
                $bookly_options['customer_information'][] = $field;
            }
        }

        return $bookly_options;
    }

    /**
     * @inheritDoc
     */
    public static function validate( Request $request )
    {
        if ( in_array( 'customer_information', $request->getSettings()->get( 'details_fields_show' ), true ) ) {
            $customer_information_errors = array();
            $errors = Lib\ProxyProviders\Local::validate( array(), $request->getCustomerInformation() );
            if ( $errors ) {
                foreach ( $errors['info_fields'] as $id => $error ) {
                    $customer_information_errors[ $id ] = $error;
                }
            }
            if ( $customer_information_errors ) {
                $request->addNotice( array( 'customer_information' => $customer_information_errors ) );
            }
        }
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearanceData( array $bookly_options )
    {
        $customer_information = array();
        foreach ( Lib\ProxyProviders\Local::getTranslatedFields() as $field ) {
            if ( ! property_exists( $field, 'visible' ) || $field->visible ) {
                $customer_information[] = array(
                    'id' => $field->id,
                    'label' => $field->label
                );
            }
        }
        $bookly_options['details_fields']['customer_information'] = __( 'Customer information', 'bookly' );
        $bookly_options['fields']['customer_information'] = __( 'Customer information', 'bookly' );
        $bookly_options['customer_information'] = $customer_information;

        return $bookly_options;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearance( array $bookly_options )
    {
        foreach ( Lib\ProxyProviders\Local::getTranslatedFields() as $field ) {
            if ( ! property_exists( $field, 'visible' ) || $field->visible ) {
                if ( ! isset( $bookly_options['details_fields_width']['customer_information'][ $field->id ] ) ) {
                    $bookly_options['details_fields_width']['customer_information'][ $field->id ] = 12;
                }
            }
        }

        return $bookly_options;
    }
}