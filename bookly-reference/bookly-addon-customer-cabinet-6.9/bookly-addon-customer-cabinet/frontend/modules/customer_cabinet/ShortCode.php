<?php
namespace BooklyCustomerCabinet\Frontend\Modules\CustomerCabinet;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules as BooklyModules;

class ShortCode extends BooklyLib\Base\ShortCode
{
    public static $code = 'bookly-customer-cabinet';

    /**
     * Link styles.
     */
    public static function linkStyles()
    {
        if ( get_option( 'bookly_cst_phone_default_country' ) !== 'disabled' ) {
            self::enqueueStyles( array(
                'bookly' => array( 'frontend/resources/css/intlTelInput.css' ),
            ) );
        }
        self::enqueueStyles( array(
            // bookly-frontend-globals carries frontend-reset (theme-bleed hardening for the
            // .bookly-css-root appointments table).
            'alias' => array( 'bookly-frontend-globals' ),
            'bookly' => array( 'backend/resources/css/fontawesome-all.min.css' => array( 'bookly-backend-globals' ) ),
            'module' => array( 'css/customer-cabinet.css' ),
        ) );
    }

    /**
     * Link scripts.
     */
    public static function linkScripts()
    {
        if ( get_option( 'bookly_cst_phone_default_country' ) !== 'disabled' ) {
            self::enqueueScripts( array(
                'bookly' => array( 'frontend/resources/js/intlTelInput.min.js' => array( 'jquery' ) ),
            ) );
        }
        self::enqueueScripts( array(
            'module' => array( 'js/customer-cabinet.js' => array( 'bookly-backend-globals' ) ),
        ) );

        $services = BooklyLib\Entities\Service::query( 's' )
            ->select( 's.id, s.title' )
            ->where( 'type', BooklyLib\Entities\Service::TYPE_SIMPLE )
            ->where( 'visibility', BooklyLib\Entities\Service::VISIBILITY_PUBLIC )
            ->fetchArray();
        $staff_members = BooklyLib\Entities\Staff::query( 's' )->select( 's.id, s.full_name' )->where( 'visibility', 'public' )->fetchArray();

        wp_localize_script( 'bookly-customer-cabinet.js', 'BooklyCustomerCabinetL10n', array(
            'datatables' => BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::CUSTOMER_CABINET_APPOINTMENTS ),
            'filters' => array(
                'date' => __( 'Date', 'bookly' ),
                'staff' => BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_employee' ),
                'service' => BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' ),
                'searchPlaceholder' => __( 'Search', 'bookly' ) . '…',
            ),
            'filterOptions' => array(
                'staff' => $staff_members,
                'services' => $services,
            ),
            'zeroRecords' => __( 'No appointments.', 'bookly' ),
            'emptyTable' => __( 'No data available in table', 'bookly' ),
            'processing' => __( 'Processing', 'bookly' ) . '…',
            'loadingRecords' => __( 'Loading...', 'bookly' ),
            'minDate' => 0,
            'maxDate' => BooklyLib\Config::getMaximumAvailableDaysForBooking(),
            'dateRange' => BooklyLib\Utils\DateTime::dateRangeOptions( array( 'anyTime' => __( 'Any time', 'bookly' ) ) ),
            'tasks' => array(
                'enabled' => BooklyLib\Config::tasksActive(),
                'title' => BooklyModules\Appointments\Proxy\Tasks::getFilterText(),
            ),
            'expired_appointment' => __( 'Expired', 'bookly' ),
            'deny_cancel_appointment' => __( 'Not allowed', 'bookly' ),
            'cancel' => __( 'Cancel', 'bookly' ),
            'payment' => __( 'Payment', 'bookly' ),
            'reschedule' => __( 'Reschedule', 'bookly' ),
            'noTimeslots' => __( 'There are no time slots for selected date.', 'bookly' ),
            'profile_update_success' => __( 'Profile updated successfully.', 'bookly' ),
            'errors' => array(
                'cancel' => __( 'Unfortunately, you\'re not able to cancel the appointment because the required time limit prior to canceling has expired.', 'bookly' ),
                'reschedule' => __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' ),
            ),
            'are_you_sure' => __( 'Are you sure?', 'bookly' ),
        ) );
    }

    /**
     * Render Customer Services shortcode.
     *
     * @param array $attributes
     * @return string
     */
    public static function render( $attributes )
    {
        global $sitepress;

        // Disable caching.
        BooklyLib\Utils\Common::noCache();

        // Prepare URL for AJAX requests.
        $ajax_url = admin_url( 'admin-ajax.php' );

        // Support WPML.
        if ( $sitepress instanceof \SitePress ) {
            $ajax_url = add_query_arg( array( 'lang' => $sitepress->get_current_language() ), $ajax_url );
        }

        $customer = new BooklyLib\Entities\Customer();
        if ( is_user_logged_in() && $customer->loadBy( array( 'wp_user_id' => get_current_user_id() ) ) ) {
            $titles = BooklyLib\Utils\Tables::getColumns( BooklyLib\Utils\Tables::CUSTOMER_CABINET_APPOINTMENTS );

            $customer_address = array(
                'country' => $customer->getCountry(),
                'state' => $customer->getState(),
                'postcode' => $customer->getPostcode(),
                'city' => $customer->getCity(),
                'street' => $customer->getStreet(),
                'street_number' => $customer->getStreetNumber(),
                'additional_address' => $customer->getAdditionalAddress(),
            );

            $appointment_columns = isset( $attributes['appointments'] ) ? explode( ',', $attributes['appointments'] ) : array();
            $filters = in_array( 'filters', $appointment_columns );
            $show_reason = in_array( 'reason', $appointment_columns );
            $show_timezone = in_array( 'timezone', $appointment_columns );
            foreach ( $appointment_columns as $pos => $column ) {
                if ( ! array_key_exists( $column, $titles ) ) {
                    unset( $appointment_columns[ $pos ] );
                }
            }
            $services = BooklyLib\Entities\Service::query( 's' )
                ->select( 's.id, s.title' )
                ->where( 'type', BooklyLib\Entities\Service::TYPE_SIMPLE )
                ->where( 'visibility', BooklyLib\Entities\Service::VISIBILITY_PUBLIC )
                ->fetchArray();

            $staff_members = BooklyLib\Entities\Staff::query( 's' )->select( 's.id, s.full_name' )->where( 'visibility', 'public' )->fetchArray();

            return self::renderTemplate( 'short_code', array(
                'ajax_url' => $ajax_url,
                'appointment_columns' => $appointment_columns,
                'filters' => $filters,
                'show_reason' => $show_reason,
                'show_timezone' => $show_timezone,
                'attributes' => (array) $attributes,
                'customer' => $customer,
                'customer_address' => $customer_address,
                'form_id' => uniqid( 'bookly-js-customer-cabinet-', false ),
                'titles' => $titles,
                'services' => $services,
                'staff_members' => $staff_members,
            ), false );
        }

        return self::renderTemplate( 'permission', array(), false );
    }
}