<?php
namespace BooklyPro\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Components\Settings\Menu;
use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;
use BooklyPro\Lib\Config;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function enqueueAssets()
    {
        self::enqueueScripts( array(
            'module' => array( 'js/settings-pro.js' => array( 'bookly-backend-globals' ) ),
        ) );

        wp_localize_script( 'bookly-settings-pro.js', 'BooklyProSettings10n', array(
            'zoomFailed' => esc_html__( 'Zoom connection failed', 'bookly' ),
            'wcSelectBookingProduct' => esc_html__( 'Please select a Booking product', 'bookly' ),
        ) );
    }

    /**
     * @inheritDoc
     */
    public static function prepareCalendarAppointmentCodes( array $codes, $participants )
    {
        if ( $participants == 'one' ) {
            $codes['client_address'] = __( 'Address of client', 'bookly' );
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( array $codes, $section )
    {
        switch ( $section ) {
            case 'calendar_one_participant':
                $codes['client_address'] = array( 'description' => __( 'Address of client', 'bookly' ) );
                $codes['client_birthday'] = array( 'description' => __( 'Client birthday', 'bookly' ), 'if' => true );
                $codes['client_full_birthday'] = array( 'description' => __( 'Client birthday full date', 'bookly' ), 'if' => true );
                break;
            case 'calendar_many_participants':
                $codes = array_merge_recursive( $codes, array(
                    'participants' => array(
                        'loop' => array(
                            'codes' => array(
                                'client_birthday' => array( 'description' => __( 'Client birthday', 'bookly' ), 'if' => true ),
                                'client_full_birthday' => array( 'description' => __( 'Client birthday full date', 'bookly' ), 'if' => true ),
                                'number_of_persons' => array( 'description' => __( 'Number of persons', 'bookly' ) ),
                            ),
                        ),
                    ),
                ) );
                break;
            case 'woocommerce':
                $codes = array_merge( $codes, array(
                    'appointment_date' => array( 'description' => __( 'Date of appointment', 'bookly' ), 'if' => true ),
                    'appointment_end_date' => array( 'description' => __( 'End date of appointment', 'bookly' ), 'if' => true ),
                    'appointment_end_time' => array( 'description' => __( 'End time of appointment', 'bookly' ), 'if' => true ),
                    'appointment_time' => array( 'description' => __( 'Time of appointment', 'bookly' ), 'if' => true ),
                    'category_info' => array( 'description' => __( 'Info of category', 'bookly' ), 'if' => true ),
                    'category_name' => array( 'description' => __( 'Name of category', 'bookly' ) ),
                    'number_of_persons' => array( 'description' => __( 'Number of persons', 'bookly' ) ),
                    'service_info' => array( 'description' => __( 'Info of service', 'bookly' ), 'if' => true ),
                    'service_name' => array( 'description' => __( 'Name of service', 'bookly' ) ),
                    'service_price' => array( 'description' => __( 'Price of service', 'bookly' ) ),
                    'staff_category_info' => array( 'description' => __( 'Info of staff category', 'bookly' ), 'if' => true ),
                    'staff_category_name' => array( 'description' => __( 'Name of staff category', 'bookly' ) ),
                    'staff_info' => array( 'description' => __( 'Info of staff', 'bookly' ), 'if' => true ),
                    'staff_name' => array( 'description' => __( 'Name of staff', 'bookly' ) ),
                    'extras' => array( 'description' => __( 'Extras titles', 'bookly' ) ),
                    'extras_total_price' => array( 'description' => __( 'Extras total price', 'bookly' ) ),
                ) );
                break;
            case 'customer_address':
                foreach ( Lib\Utils\Common::getAddressFields() as $field => $description ) {
                    $codes[ $field ] = array( 'description' => $description, 'if' => true );
                }
                break;
            case 'google_calendar':
            case 'outlook_calendar':
            case 'apple_calendar':
                $codes = array_merge_recursive( $codes, array(
                    'appointment_id' => array( 'description' => __( 'Appointment ID', 'bookly' ) ),
                    'appointment_date' => array( 'description' => __( 'Date of appointment', 'bookly' ) ),
                    'appointment_notes' => array( 'description' => __( 'Customer notes for appointment', 'bookly' ), 'if' => true ),
                    'appointment_time' => array( 'description' => __( 'Time of appointment', 'bookly' ) ),
                    'booking_number' => array( 'description' => __( 'Booking number', 'bookly' ) ),
                    'category_info' => array( 'description' => __( 'Info of category', 'bookly' ), 'if' => true ),
                    'category_name' => array( 'description' => __( 'Name of category', 'bookly' ) ),
                    'company_address' => array( 'description' => __( 'Address of company', 'bookly' ) ),
                    'company_name' => array( 'description' => __( 'Name of company', 'bookly' ) ),
                    'company_phone' => array( 'description' => __( 'Company phone', 'bookly' ) ),
                    'company_website' => array( 'description' => __( 'Company web-site address', 'bookly' ) ),
                    'internal_note' => array( 'description' => __( 'Internal note', 'bookly' ) ),
                    'online_meeting_password' => array( 'description' => __( 'Online meeting password', 'bookly' ), 'if' => true ),
                    'online_meeting_start_url' => array( 'description' => __( 'Online meeting start URL', 'bookly' ), 'if' => true ),
                    'online_meeting_url' => array( 'description' => __( 'Online meeting URL', 'bookly' ), 'if' => true ),
                    'service_duration' => array( 'description' => __( 'Duration of service', 'bookly' ) ),
                    'service_info' => array( 'description' => __( 'Info of service', 'bookly' ), 'if' => true ),
                    'service_name' => array( 'description' => __( 'Name of service', 'bookly' ) ),
                    'service_price' => array( 'description' => __( 'Price of service', 'bookly' ) ),
                    'staff_category_info' => array( 'description' => __( 'Info of staff category', 'bookly' ), 'if' => true ),
                    'staff_category_name' => array( 'description' => __( 'Name of staff category', 'bookly' ) ),
                    'staff_email' => array( 'description' => __( 'Email of staff', 'bookly' ) ),
                    'staff_info' => array( 'description' => __( 'Info of staff', 'bookly' ), 'if' => true ),
                    'staff_name' => array( 'description' => __( 'Name of staff', 'bookly' ) ),
                    'staff_phone' => array( 'description' => __( 'Phone of staff', 'bookly' ) ),
                    'participants' => array(
                        'description' => array(
                            __( 'Loop over clients list', 'bookly' ),
                            __( 'Loop over clients list with delimiter', 'bookly' ),
                        ),
                        'loop' => array(
                            'item' => 'participant',
                            'codes' => array(
                                'appointment_id' => array( 'description' => __( 'Appointment ID', 'bookly' ) ),
                                'appointment_notes' => array( 'description' => __( 'Customer notes for appointment', 'bookly' ), 'if' => true ),
                                'booking_number' => array( 'description' => __( 'Booking number', 'bookly' ) ),
                                'client_address' => array( 'description' => __( 'Address of client', 'bookly' ) ),
                                'client_email' => array( 'description' => __( 'Email of client', 'bookly' ), 'if' => true ),
                                'client_first_name' => array( 'description' => __( 'First name of client', 'bookly' ), 'if' => true ),
                                'client_last_name' => array( 'description' => __( 'Last name of client', 'bookly' ), 'if' => true ),
                                'client_name' => array( 'description' => __( 'Full name of client', 'bookly' ) ),
                                'client_note' => array( 'description' => __( 'Note of client', 'bookly' ) ),
                                'client_phone' => array( 'description' => __( 'Phone of client', 'bookly' ), 'if' => true ),
                                'client_birthday' => array( 'description' => __( 'Client birthday', 'bookly' ), 'if' => true ),
                                'client_full_birthday' => array( 'description' => __( 'Client birthday full date', 'bookly' ), 'if' => true ),
                                'number_of_persons' => array( 'description' => __( 'Number of persons', 'bookly' ) ),
                                'payment_status' => array( 'description' => __( 'Status of payment', 'bookly' ) ),
                                'payment_type' => array( 'description' => __( 'Payment type', 'bookly' ) ),
                                'status' => array( 'description' => __( 'Status of appointment', 'bookly' ) ),
                                'amount_paid' => array( 'description' => __( 'Amount paid', 'bookly' ) ),
                                'amount_due' => array( 'description' => __( 'Amount due', 'bookly' ) ),
                                'total_price' => array( 'description' => __( 'Total price of booking (sum of all cart items after applying coupon)', 'bookly' ) ),
                                'cancel_appointment' => array( 'description' => __( 'Cancel appointment link', 'bookly' ) ),
                                'cancel_appointment_url' => array( 'description' => __( 'URL of cancel appointment link (to use inside <a> tag)', 'bookly' ) ),
                            ),
                        ),
                    ),
                ) );
                break;
            case 'ics_for_staff':
                $codes['online_meeting_join_url'] = array( 'description' => __( 'Online meeting join URL', 'bookly' ), 'if' => true );
                $codes['online_meeting_password'] = array( 'description' => __( 'Online meeting password', 'bookly' ), 'if' => true );
                $codes['online_meeting_start_url'] = array( 'description' => __( 'Online meeting start URL', 'bookly' ), 'if' => true );
                $codes['online_meeting_url'] = array( 'description' => __( 'Online meeting URL', 'bookly' ), 'if' => true );
                $codes['staff_category_info'] = array( 'description' => __( 'Info of staff category', 'bookly' ), 'if' => true );
                $codes['staff_category_name'] = array( 'description' => __( 'Name of staff category', 'bookly' ) );
                $codes = array_merge_recursive( $codes, array(
                    'participants' => array(
                        'loop' => array(
                            'codes' => array(
                                'client_address' => array( 'description' => __( 'Address of client', 'bookly' ) ),
                                'client_birthday' => array( 'description' => __( 'Client birthday', 'bookly' ), 'if' => true ),
                                'client_full_birthday' => array( 'description' => __( 'Client birthday full date', 'bookly' ), 'if' => true ),
                                'number_of_persons' => array( 'description' => __( 'Number of persons', 'bookly' ) ),
                            ),
                        ),
                    ),
                ) );
                break;
            case 'ics_for_customer':
                $codes['client_address'] = array( 'description' => __( 'Address of client', 'bookly' ) );
                $codes['client_birthday'] = array( 'description' => __( 'Client birthday', 'bookly' ), 'if' => true );
                $codes['client_full_birthday'] = array( 'description' => __( 'Client birthday full date', 'bookly' ), 'if' => true );
                $codes['online_meeting_join_url'] = array( 'description' => __( 'Online meeting join URL', 'bookly' ), 'if' => true );
                $codes['online_meeting_password'] = array( 'description' => __( 'Online meeting password', 'bookly' ), 'if' => true );
                $codes['online_meeting_start_url'] = array( 'description' => __( 'Online meeting start URL', 'bookly' ), 'if' => true );
                $codes['online_meeting_url'] = array( 'description' => __( 'Online meeting URL', 'bookly' ), 'if' => true );
                break;
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function preparePaymentGatewaySettings( $payment_data )
    {
        $paypal = BooklyLib\Entities\Payment::TYPE_PAYPAL;
        $payment_data[ $paypal ] = self::renderTemplate( 'paypal_settings', array( 'type' => $paypal ), false );
        return $payment_data;
    }

    /**
     * @inheritDoc
     */
    public static function renderMenuItem()
    {
        Menu::renderItem( 'WooCommerce', 'woo_commerce' );
        Menu::renderItem( 'Facebook', 'facebook' );
        if ( BooklyLib\Config::multipleServicesBookingEnabled() ) {
            Menu::renderItem( __( 'Cart', 'bookly' ), 'cart' );
        }
    }

    /**
     * @inheritDoc
     */
    public static function renderTab()
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        // Products without variations
        $query =
            'SELECT p1.ID, p1.post_title
              FROM ' . $wpdb->posts . ' p1
         LEFT JOIN ' . $wpdb->posts . ' p2
                ON ( p1.ID = p2.post_parent AND p2.post_type = \'product_variation\' )
             WHERE p1.post_type = \'product\'
               AND p1.post_status = \'publish\'
               AND p2.ID IS NULL
          ORDER BY p1.post_title';

        $products = array_merge( array( array( 0, __( 'Select product', 'bookly' ) ) ), $wpdb->get_results( $query, ARRAY_N ) );
        $wc_warning = false;
        $order_statuses = array();
        if ( class_exists( 'WooCommerce', false ) ) {
            if ( get_option( 'bookly_wc_enabled' ) ) {
                $post = get_post( wc_get_page_id( 'cart' ) );
                if ( $post === null || $post->post_status != 'publish' ) {
                    $wc_warning = sprintf(
                        __( 'WooCommerce cart is not set up. Follow the <a href="%s">link</a> to correct this problem.', 'bookly' ),
                        BooklyLib\Utils\Common::escAdminUrl( 'wc-status', array( 'tab' => 'tools' ) )
                    );
                }
            }
            foreach ( wc_get_order_statuses() as $status => $title ) {
                $order_statuses[] = array( $status, $title );
            }
        }
        self::renderTemplate( 'wc_tab', compact( 'products', 'wc_warning', 'order_statuses' ) );

        self::renderTemplate( 'fb_tab' );

        if ( BooklyLib\Config::multipleServicesBookingEnabled() ) {
            $cart_columns = array(
                'service' => __( 'Service', 'bookly' ),
                'date' => __( 'Date', 'bookly' ),
                'time' => __( 'Time', 'bookly' ),
                'employee' => __( 'Staff', 'bookly' ),
                'price' => __( 'Price', 'bookly' ),
                'deposit' => _x( 'Deposit', 'portion of the payment', 'bookly' ),
                'tax' => __( 'Tax', 'bookly' ),
            );
            self::renderTemplate( 'cart_tab', compact( 'cart_columns' ) );
        }

        self::renderTemplate( 'additional_tab' );

        $connected = (bool) Config::zoomOAuthToken();
        self::renderTemplate( 'online_meetings_tab', compact( 'connected' ) );

        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $roles = apply_filters( 'editable_roles', $all_roles );

        self::renderTemplate( 'user_permissions_tab', compact( 'roles' ) );

        $fetch_limits = array(
            array( '0', __( 'Disabled', 'bookly' ) ),
            array( 25, 25 ),
            array( 50, 50 ),
            array( 100, 100 ),
            array( 250, 250 ),
            array( 500, 500 ),
            array( 1000, 1000 ),
            array( 2500, 2500 )
        );

        self::renderTemplate( 'google_calendar_tab', compact( 'fetch_limits' ) );
    }

    /**
     * @inheritDoc
     */
    public static function saveSettings( array $alert, $tab, array $params )
    {
        $options = array();
        switch ( $tab ) {
            case 'additional':
                $options = array(
                    'bookly_cal_frontend_enabled',
                    'bookly_cal_frontend_display_only_busy_slots',
                    'bookly_combined_price_method',
                );
                break;
            case 'appointments':
                $options = array(
                    'bookly_appointments_main_value',
                    'bookly_appointments_time_delimiter',
                    'bookly_appointments_displayed_time_slots',
                    'bookly_appointment_cancel_action',
                    'bookly_auto_change_status',
                    'bookly_auto_change_status_from',
                    'bookly_auto_change_status_to',
                );
                break;
            case 'customers':
                $options = array(
                    'bookly_cst_address_show_fields',
                    'bookly_cst_create_account',
                    'bookly_cst_limit_statuses',
                    'bookly_cst_new_account_role',
                    'bookly_cst_required_address',
                    'bookly_cst_required_birthday',
                    'bookly_l10n_cst_address_template',
                );
                if ( ! array_key_exists( 'bookly_cst_limit_statuses', $params ) ) {
                    $params['bookly_cst_limit_statuses'] = array();
                }
                break;
            case 'facebook':
                $options = array( 'bookly_fb_app_id' );
                if ( $params['bookly_fb_app_id'] == '' ) {
                    update_option( 'bookly_app_show_facebook_login_button', 0 );
                }
                $alert['success'][] = __( 'Settings saved.', 'bookly' );
                break;
            case 'general':
                $options = array( 'bookly_gen_min_time_prior_booking', 'bookly_gen_min_time_prior_cancel' );
                break;
            case 'google_calendar':
                $alert = Proxy\AdvancedGoogleCalendar::preSaveSettings( $alert, $params );
                $options = array(
                    'bookly_gc_client_id',
                    'bookly_gc_client_secret',
                    'bookly_gc_sync_mode',
                    'bookly_gc_limit_events',
                    'bookly_gc_event_title',
                    'bookly_gc_event_description',
                );
                $alert['success'][] = __( 'Settings saved.', 'bookly' );
                break;
            case 'payments':
                $options = array(
                    'bookly_paypal_enabled',
                    'bookly_paypal_api_username',
                    'bookly_paypal_api_password',
                    'bookly_paypal_api_signature',
                    'bookly_paypal_sandbox',
                    'bookly_paypal_increase',
                    'bookly_paypal_addition',
                    'bookly_paypal_send_tax',
                    'bookly_paypal_timeout',
                );
                break;
            case 'url':
                $options = array( 'bookly_url_final_step_url', 'bookly_url_cancel_confirm_page_url' );
                break;
            case 'woo_commerce':
                $options = array(
                    'bookly_l10n_wc_cart_info_name',
                    'bookly_l10n_wc_cart_info_value',
                    'bookly_wc_enabled',
                    'bookly_wc_product',
                    'bookly_wc_create_order_at_zero_cost',
                    'bookly_wc_create_order_via_backend',
                );
                if ( class_exists( 'WooCommerce', false ) ) {
                    $options[] = 'bookly_wc_default_order_status';
                }
                $alert['success'][] = __( 'Settings saved.', 'bookly' );
                break;
            case 'cart':
                $options = array( 'bookly_cart_show_columns' );
                $alert['success'][] = __( 'Settings saved.', 'bookly' );
                break;
            case 'online_meetings':
                $options = array(
                    'bookly_zoom_authentication',
                    'bookly_zoom_oauth_client_id',
                    'bookly_zoom_oauth_client_secret',
                    'bookly_bbb_server_end_point',
                    'bookly_bbb_shared_secret',
                    'bookly_jitsi_server',
                );
                // Check current zoom authorization value
                if ( Lib\Config::zoomAuthentication() !== $params['bookly_zoom_authentication'] ) {
                    // Reset zoom authorization to default for all staff
                    BooklyLib\Entities\Staff::query()->update()->set( 'zoom_authentication', Lib\Zoom\Authentication::TYPE_DEFAULT )->execute();
                    $alert['success'][] = __( 'All staff members will use these settings when connecting to Zoom', 'bookly' );
                } else {
                    $alert['success'][] = __( 'Settings saved.', 'bookly' );
                }
                break;
            case 'user_permissions':
                global $wp_roles;
                $all_roles = $wp_roles->roles;
                $roles = apply_filters( 'editable_roles', $all_roles );
                $manage_bookly_appointments = isset( $params['manage_bookly_appointments'] ) ? $params['manage_bookly_appointments'] : array();
                $manage_bookly = isset( $params['manage_bookly'] ) ? $params['manage_bookly'] : array();
                $admin = current_user_can( 'manage_options' );
                foreach ( $roles as $role => $data ) {
                    if ( ! array_key_exists( 'manage_options', $data['capabilities'] ) ) {
                        if ( array_key_exists( 'manage_bookly_appointments', $data['capabilities'] ) && ! in_array( $role, $manage_bookly_appointments ) ) {
                            $wp_roles->remove_cap( $role, 'manage_bookly_appointments' );
                        }
                        if ( ! array_key_exists( 'manage_bookly_appointments', $data['capabilities'] ) && in_array( $role, $manage_bookly_appointments ) ) {
                            $wp_roles->add_cap( $role, 'manage_bookly_appointments' );
                        }
                        if ( $admin ) {
                            if ( array_key_exists( 'manage_bookly', $data['capabilities'] ) && ! in_array( $role, $manage_bookly ) ) {
                                $wp_roles->remove_cap( $role, 'manage_bookly' );
                            }
                            if ( ! array_key_exists( 'manage_bookly', $data['capabilities'] ) && in_array( $role, $manage_bookly ) ) {
                                $wp_roles->add_cap( $role, 'manage_bookly' );
                            }
                        }
                    }
                }
                $options = $admin
                    ? array(
                        'bookly_staff_new_account_role',
                    ) : array();
                $alert['success'][] = __( 'Settings saved.', 'bookly' );
                break;
            case 'calendar':
                $options = array( 'bookly_cal_frontend_enabled', 'bookly_cal_frontend_display_only_busy_slots' );
                break;
        }

        // Update options.
        foreach ( $options as $option_name ) {
            if ( array_key_exists( $option_name, $params ) ) {
                $value = $params[ $option_name ];
                update_option( $option_name, is_array( $value ) ? $value : trim( $value ) );
                if ( strncmp( $option_name, 'bookly_l10n_', 12 ) === 0 ) {
                    do_action( 'wpml_register_single_string', 'bookly', $option_name, trim( $value ) );
                }
            }
        }

        return $alert;
    }
}