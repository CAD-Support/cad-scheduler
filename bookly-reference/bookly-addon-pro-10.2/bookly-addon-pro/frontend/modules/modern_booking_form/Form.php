<?php
namespace BooklyPro\Frontend\Modules\ModernBookingForm;

use BooklyPro\Lib;
use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use Bookly\Frontend\Modules\Booking\Proxy as BookingProxy;

class Form extends BooklyLib\Base\Component
{
    public static function render()
    {
        $tel_input_enabled = get_option( 'bookly_cst_phone_default_country' ) !== 'disabled';

        self::enqueueScripts( array(
            'backend' => array(
                'js/common.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/modern-booking-form.js' => array( 'bookly-qrcode.js', 'bookly-frontend-globals', 'bookly-daterangepicker.js' ),
            ),
            'bookly' => array(
                'frontend/resources/js/qrcode.js' => array()
            ),
        ) );
        self::enqueueStyles( array(
            'module' => array( 'css/modern-booking-form.css' ),
            'bookly' => array( 'backend/resources/tailwind/tailwind.css' ),
        ) );
        if ( $tel_input_enabled ) {
            self::enqueueStyles( array(
                'bookly' => array( 'frontend/resources/css/intlTelInput.css' ),
            ) );
            self::enqueueScripts( array(
                'bookly' => array( 'frontend/resources/js/intlTelInput.min.js' => array( 'jquery' ) ),
            ) );
        }

        $customer = new BooklyLib\Entities\Customer();
        if ( is_user_logged_in() ) {
            $customer->loadBy( array( 'wp_user_id' => get_current_user_id() ) );
            if ( ! $customer->getId() ) {
                $customer->setFirstName( wp_get_current_user()->first_name );
                $customer->setLastName( wp_get_current_user()->last_name );
                $customer->setFullName( wp_get_current_user()->display_name );
                $customer->setEmail( wp_get_current_user()->user_email );
            }
        } elseif ( get_option( 'bookly_cst_remember_in_cookie' ) ) {
            if ( isset( $_COOKIE['bookly-customer-email'] ) ) {
                $customer->setEmail( BooklyLib\Utils\Common::stripWpKses( $_COOKIE['bookly-customer-email'] ) );
            }
            if ( isset( $_COOKIE['bookly-customer-phone'] ) ) {
                $customer->setPhone( BooklyLib\Utils\Common::stripWpKses( $_COOKIE['bookly-customer-phone'] ) );
            }
            if ( isset( $_COOKIE['bookly-customer-full-name'] ) ) {
                $customer->setFullName( BooklyLib\Utils\Common::stripWpKses( $_COOKIE['bookly-customer-full-name'] ) );
            }
            if ( isset( $_COOKIE['bookly-customer-first-name'] ) ) {
                $customer->setFirstName( BooklyLib\Utils\Common::stripWpKses( $_COOKIE['bookly-customer-first-name'] ) );
            }
            if ( isset( $_COOKIE['bookly-customer-last-name'] ) ) {
                $customer->setLastName( BooklyLib\Utils\Common::stripWpKses( $_COOKIE['bookly-customer-last-name'] ) );
            }
        }

        wp_localize_script(
            'bookly-modern-booking-form.js', 'BooklyL10nModernBookingForm', Proxy\Shared::prepareFormOptions( array(
            'session_id' => BooklyLib\FormSession::createSession(),
            'customer' => array(
                'id' => $customer->getId(),
                'first_name' => $customer->getFirstName(),
                'last_name' => $customer->getLastName(),
                'full_name' => $customer->getFullName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone(),
                'country' => $customer->getCountry(),
                'state' => $customer->getState(),
                'postcode' => $customer->getPostcode(),
                'city' => $customer->getCity(),
                'street' => $customer->getStreet(),
                'street_number' => $customer->getStreetNumber(),
                'additional_address' => $customer->getAdditionalAddress(),
                'full_address' => $customer->getFullAddress(),
            ),
            'complex_services' => array(),
            'gift_cards' => array(),
            'format_price' => BooklyLib\Utils\Price::formatOptions(),
            'datePicker' => BooklyLib\Utils\DateTime::datePickerOptions(),
            'maxDaysForBooking' => BooklyLib\Config::getMaximumAvailableDaysForBooking(),
            'moment_format_date' => BooklyLib\Utils\DateTime::convertFormat( 'date', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'moment_format_time' => BooklyLib\Utils\DateTime::convertFormat( 'time', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'casest' => BooklyLib\Config::getCaSeSt(),
            'use_client_timezone' => BooklyLib\Config::useClientTimeZone(),
            'timezones' => BooklyLib\Utils\DateTime::getTimeZoneOptions(),
            'images' => plugins_url( 'frontend/resources/images/', BooklyLib\Plugin::getMainFile() ),
            'payment_disabled' => BooklyLib\Config::paymentStepDisabled() || BookingProxy\CustomerGroups::getSkipPayment( $customer ),
            'intlTelInput' => array(
                'enabled' => $tel_input_enabled,
                'country' => get_option( 'bookly_cst_phone_default_country' ),
            ),
            'l10n' => array(
                'prev' => __( 'Previous', 'bookly' ),
                'next' => __( 'Next', 'bookly' ),
                'cart' => __( 'Cart', 'bookly' ),
            ),
        ) )
        );
    }
}