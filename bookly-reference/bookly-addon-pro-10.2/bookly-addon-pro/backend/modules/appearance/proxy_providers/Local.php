<?php
namespace BooklyPro\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;
use Bookly\Backend\Modules\Appearance;
use Bookly\Backend\Components\Controls\Inputs;
use Bookly\Frontend\Modules as BooklyModules;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;
use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\PaymentFlow;

class Local extends Proxy\Pro
{
    /**
     * @inheritDoc
     */
    public static function renderBookingStatesSelector()
    {
        self::renderTemplate( 'booking_states_selector' );
    }

    /**
     * @inheritDoc
     */
    public static function renderBookingStatesText()
    {
        self::renderTemplate( 'booking_states_text' );
    }

    /**
     * @inheritDoc
     */
    public static function renderPaymentImpossible()
    {
        self::renderTemplate( 'payment_impossible' );
    }

    /**
     * @inheritDoc
     */
    public static function renderShowStepDetailsSettings()
    {
        self::renderTemplate( 'show_details' );
    }

    /**
     * @inheritDoc
     */
    public static function renderTimeZoneSwitcher()
    {
        $current_offset = get_option( 'gmt_offset' );
        $tz_string = get_option( 'timezone_string' );
        if ( $tz_string == '' ) { // Create a UTC+- zone if no timezone string exists
            if ( $current_offset == 0 ) {
                $tz_string = 'UTC+0';
            } elseif ( $current_offset < 0 ) {
                $tz_string = 'UTC' . $current_offset;
            } else {
                $tz_string = 'UTC+' . $current_offset;
            }
        }

        self::renderTemplate( 'time_zone_switcher', compact( 'tz_string' ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderTimeZoneSwitcherCheckbox()
    {
        self::renderTemplate( 'time_zone_switcher_checkbox' );
    }

    /**
     * @inheritDoc
     */
    public static function renderFacebookButton()
    {
        self::renderTemplate( 'fb_button' );
    }

    /**
     * @inheritDoc
     */
    public static function renderTips()
    {
        self::renderTemplate( 'tips' );
    }

    /**
     * @inheritDoc
     */
    public static function renderShowTips()
    {
        self::renderTemplate( 'show_tips' );
    }

    /**
     * @inheritDoc
     */
    public static function renderAddress()
    {
        $address_is_required = BooklyLib\Config::addressRequired();
        $address = array();
        foreach ( Lib\Utils\Common::getDisplayedAddressFields() as $field_name => $field ) {
            $labels = array( 'bookly_l10n_label_' . $field_name );
            if ( $address_is_required ) {
                $labels[] = 'bookly_l10n_required_' . $field_name;
            }
            $id = 'bookly-js-address-' . $field_name;
            $address[ $id ] = $labels;
        }
        self::renderTemplate( 'address', compact( 'address' ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderBirthday()
    {
        // Render HTML.
        $fields = array();
        foreach ( BooklyLib\Utils\DateTime::getDatePartsOrder() as $type ) {
            $fields[] = self::_renderEditableField( $type );
        }

        self::renderTemplate( 'birthday', compact( 'fields' ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderShowQRCode()
    {
        echo '<div class="col-md-3 my-2">';
        echo '<div id="bookly-show-appointment-qr-popover" data-container="#bookly-show-appointment-qr-popover" data-toggle="bookly-popover" data-placement="bottom" data-content="' . esc_attr__( 'Please note that QR code will be shown only for single appointments', 'bookly' ) . '">';
        Inputs::renderCheckBox( __( 'Show QR code', 'bookly' ), null, get_option( 'bookly_app_show_appointment_qr' ), array( 'id' => 'bookly-show-appointment-qr' ) );
        echo '</div></div>';
    }

    /**
     * @inheritDoc
     */
    public static function renderQRCode()
    {
        self::renderTemplate( 'qr_code' );
    }

    /**
     * Render single editable field of given type.
     *
     * @param string $type
     * @return string
     */
    protected static function _renderEditableField( $type )
    {
        $editable = array( 'bookly_l10n_label_birthday_' . $type, 'bookly_l10n_option_' . $type, 'bookly_l10n_required_' . $type );
        $empty = get_option( 'bookly_l10n_option_' . $type );
        $options = array();

        switch ( $type ) {
            case 'day':
                $editable[] = 'bookly_l10n_invalid_day';
                $options = Lib\Utils\Common::dayOptions();
                break;
            case 'month':
                $options = Lib\Utils\Common::monthOptions();
                break;
            case 'year':
                $options = Lib\Utils\Common::yearOptions();
                break;
        }

        return self::renderTemplate( 'birthday_fields', compact( 'type', 'editable', 'empty', 'options' ), false );
    }

    /**
     * Render appearance
     */
    public static function renderModernAppearance()
    {
        self::enqueueScripts( array(
            'module' => array(
                'js/modern-appearance.js' => array( 'jquery', 'bookly-backend-globals' ),
            ),
        ) );

        $appearances = array(
            Lib\Entities\Form::TYPE_SEARCH_FORM => array(
                'id' => Lib\Entities\Form::TYPE_SEARCH_FORM,
                'title' => __( 'Search form', 'bookly' ),
                'description' => __( 'Modern, fast, and smooth form that allows your customers to easily find the right service.', 'bookly' ),
                'img' => plugins_url( 'backend/modules/appearance/resources/images/appearance-search-form.png', Lib\Plugin::getMainFile() ),
                'appearance' => self::getAppearance( Lib\Entities\Form::TYPE_SEARCH_FORM ),
                'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . Lib\Entities\Form::TYPE_SEARCH_FORM,
            ),
            Lib\Entities\Form::TYPE_TAGS_FORM => array(
                'id' => Lib\Entities\Form::TYPE_TAGS_FORM,
                'title' => __( 'Tags form', 'bookly' ),
                'description' => __( 'This form allows you to organize the structure and navigation based on tags assigned to services.', 'bookly' ),
                'img' => plugins_url( 'backend/modules/appearance/resources/images/appearance-tags-form.png', Lib\Plugin::getMainFile() ),
                'appearance' => self::getAppearance( Lib\Entities\Form::TYPE_TAGS_FORM ),
                'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . Lib\Entities\Form::TYPE_TAGS_FORM,
                'help_url' => 'https://hub.bookly.pro/go/bookly-tags-form',
            ),
            Lib\Entities\Form::TYPE_SERVICES_FORM => array(
                'id' => Lib\Entities\Form::TYPE_SERVICES_FORM,
                'title' => __( 'Services form', 'bookly' ),
                'description' => __( 'Catalog view allows you to organize and display the services conveniently for your customers.', 'bookly' ),
                'img' => plugins_url( 'backend/modules/appearance/resources/images/appearance-services-form.png', Lib\Plugin::getMainFile() ),
                'appearance' => self::getAppearance( Lib\Entities\Form::TYPE_SERVICES_FORM ),
                'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . Lib\Entities\Form::TYPE_SERVICES_FORM,
            ),
            Lib\Entities\Form::TYPE_STAFF_FORM => array(
                'id' => Lib\Entities\Form::TYPE_STAFF_FORM,
                'title' => __( 'Staff form', 'bookly' ),
                'description' => __( 'Catalog view allows you to organize and display the staff conveniently for your customers.', 'bookly' ),
                'img' => plugins_url( 'backend/modules/appearance/resources/images/appearance-staff-form.png', Lib\Plugin::getMainFile() ),
                'appearance' => self::getAppearance( Lib\Entities\Form::TYPE_STAFF_FORM ),
                'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . Lib\Entities\Form::TYPE_STAFF_FORM,
            ),
            Lib\Entities\Form::TYPE_BOOKLY_FORM => array(
                'id' => Lib\Entities\Form::TYPE_BOOKLY_FORM,
                'title' => __( 'Step by step form', 'bookly' ),
                'description' => __( 'Classic booking form with the consequent scheduling process.', 'bookly' ),
                'img' => plugins_url( 'backend/modules/appearance/resources/images/appearance-bookly-form.png', Lib\Plugin::getMainFile() ),
                'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . Lib\Entities\Form::TYPE_BOOKLY_FORM,
            ),
            Lib\Entities\Form::TYPE_CHECKOUT_FORM => array(
                'id' => Lib\Entities\Form::TYPE_CHECKOUT_FORM,
                'title' => __( 'Checkout form', 'bookly' ),
                'description' => __( 'Lightweight form that allows your customers to pay for appointments, events, and more.', 'bookly' ),
                'img' => plugins_url( 'backend/modules/appearance/resources/images/checkout-form.png', Lib\Plugin::getMainFile() ),
                'appearance' => self::getAppearance( Lib\Entities\Form::TYPE_CHECKOUT_FORM ),
                'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . Lib\Entities\Form::TYPE_CHECKOUT_FORM,
                'help_url' => 'https://hub.bookly.pro/go/bookly-checkout-form',
            ),
            Lib\Entities\Form::TYPE_CANCELLATION_FORM => array(
                'id' => Lib\Entities\Form::TYPE_CANCELLATION_FORM,
                'title' => __( 'Cancellation form', 'bookly' ),
                'description' => __( 'Lightweight form that allows your customers to cancel their appointments and optionally specify the cancellation reason.', 'bookly' ),
                'img' => plugins_url( 'backend/modules/appearance/resources/images/cancellation-form.png', Lib\Plugin::getMainFile() ),
                'appearance' => self::getAppearance( Lib\Entities\Form::TYPE_CANCELLATION_FORM ),
                'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . Lib\Entities\Form::TYPE_CANCELLATION_FORM,
            ),
        );

        $appearances = Proxy\Shared::prepareAppearances( $appearances );

        $payment_systems = array();
        foreach ( PaymentFlow::orderGateways( PaymentFlow::getSupportedGateways() ) as $gateway ) {
            $payment_systems[ $gateway ] = array(
                'title' => BooklyLib\Entities\Payment::typeToString( $gateway ),
                'image' => BooklyLib\Entities\Payment::typeToImage( $gateway ),
            );
        }

        $categories = array( '-1' => array( 'id' => '-1', 'title' => __( 'Uncategorized', 'bookly' ) ) );
        $rows = BooklyLib\Entities\Category::query( 'c' )->select( 'c.id, c.name' )->sortBy( 'c.position' )->fetchArray();
        foreach ( $rows as $row ) {
            $categories[] = array( 'id' => $row['id'], 'title' => BooklyLib\Utils\Common::getTranslatedString( 'category_' . $row['id'], $row['name'] ) );
        }

        $staff_categories = array( '-1' => array( 'id' => '-1', 'title' => __( 'Uncategorized', 'bookly' ) ) );
        $rows = Lib\Entities\StaffCategory::query( 'c' )->select( 'c.id, c.name' )->sortBy( 'c.position' )->fetchArray();
        foreach ( $rows as $row ) {
            $staff_categories[] = array( 'id' => $row['id'], 'title' => BooklyLib\Utils\Common::getTranslatedString( 'staff_category_' . $row['id'], $row['name'] ) );
        }

        $services = array();
        $rows = BooklyLib\Entities\Service::query( 's' )->select( 's.id, s.title' )->whereNot( 'type', BooklyLib\Entities\Service::TYPE_PACKAGE )->sortBy( 's.position' )->fetchArray();
        foreach ( $rows as $row ) {
            $services[] = array( 'id' => $row['id'], 'title' => $row['title'] === '' ? __( 'Untitled', 'bookly' ) : BooklyLib\Utils\Common::getTranslatedString( 'service_' . $row['id'], $row['title'] ) );
        }

        $tags = Lib\Entities\Tag::query()
            ->select( 'tag, color_id' )
            ->where( 'type', Lib\Entities\Tag::TYPE_SERVICE )
            ->fetchArray();

        $fields = array(
            'placeholder' => __( 'Placeholder', 'bookly' ),
            'empty_option' => __( 'Empty option', 'bookly' ),
            'service_card_width' => __( 'Width', 'bookly' ),
            'card_header_height' => __( 'Header height', 'bookly' ),
            'card_body_height' => __( 'Body height', 'bookly' ),
            'card_width' => __( 'Width', 'bookly' ),
            'service_header_height' => __( 'Header height', 'bookly' ),
            'service_body_height' => __( 'Body height', 'bookly' ),
            'staff_card_width' => __( 'Width', 'bookly' ),
            'staff_card_header_height' => __( 'Header height', 'bookly' ),
            'staff_card_body_height' => __( 'Body height', 'bookly' ),
            'category_card_width' => __( 'Width', 'bookly' ),
            'category_header_height' => __( 'Header height', 'bookly' ),
            'category_body_height' => __( 'Body height', 'bookly' ),
            'step_service_card_width' => __( 'Width', 'bookly' ),
            'step_service_card_header_height' => __( 'Header height', 'bookly' ),
            'step_service_card_body_height' => __( 'Body height', 'bookly' ),
            'form_title' => __( 'Form title', 'bookly' ),
            'form_slug' => __( 'Slug', 'bookly' ),
            'main_color' => __( 'Main color', 'bookly' ),
            'hide_borders' => __( 'Hide form borders', 'bookly' ),
            'show' => __( 'Show', 'bookly' ),
            'show_book_more' => __( 'Show \'Book more\' button', 'bookly' ),
            'show_extras_price' => __( 'Show price', 'bookly' ),
            'show_extras_summary' => __( 'Show summary', 'bookly' ),
            'show_filter' => __( 'Show', 'bookly' ),
            'show_calendar' => __( 'Show', 'bookly' ),
            'show_qr_code' => __( 'Show QR Code', 'bookly' ),
            'show_reason' => __( 'Show cancellation reason', 'bookly' ),
            'show_card_badge' => __( 'Show badge', 'bookly' ),
            'default_value' => __( 'Default value', 'bookly' ),
            'categories_list' => __( 'Categories list', 'bookly' ),
            'category_any' => __( 'Any', 'bookly' ),
            'category_custom' => __( 'Custom', 'bookly' ),
            'services_list' => __( 'Services list', 'bookly' ),
            'service_any' => __( 'Any', 'bookly' ),
            'service_custom' => __( 'Custom', 'bookly' ),
            'text' => __( 'Text', 'bookly' ),
            'skip_categories_step' => __( 'Hide categories step', 'bookly' ),
            'skip_services_step' => __( 'Hide services step', 'bookly' ),
            'skip_tags_step' => __( 'Hide tags step', 'bookly' ),
            'staff_list' => __( 'Staff list', 'bookly' ),
            'staff_any' => __( 'Any', 'bookly' ),
            'staff_custom' => __( 'Custom', 'bookly' ),
            'skip_staff_step' => __( 'Hide staff step', 'bookly' ),
            'card_content' => __( 'Card content', 'bookly' ),
            'address_fields' => __( 'Customer\'s address fields', 'bookly' ),
            'show_first_slot_only' => __( 'Show only first available timeslot', 'bookly' ),
            'button' => __( 'Button', 'bookly' ),
            'display_services' => __( 'Display services', 'bookly' ),
            'width' => __( 'Width', 'bookly' ),
            'details_fields_alert' => __( 'It is important to leave at least one visible field with personal info (Full name, First name or Last name) and one field with contact info (Email or Phone), otherwise the booking will be created without required customer details.', 'bookly' ) . '<br><b>' . __( 'Please note that email address is required for some payment gateways in order to complete a transaction.', 'bookly' ) . '</b>',
            'details_email_field_alert' => __( 'Please note that email address is required for some payment gateways in order to complete a transaction.', 'bookly' ),
            'step_content' => __( 'Step content', 'bookly' ),
            'cards_display_mode' => __( 'Cards display mode', 'bookly' ),
            'show_all_cards' => __( 'Show all available cards', 'bookly' ),
            'show_with_slots' => __( 'Show cards with time slots only', 'bookly' ),
            'required' => __( 'Required', 'bookly' ),
            'use_cart_local_storage' => __( 'Save cart locally', 'bookly' ),
            'verify_credentials' => __( 'Verify credentials', 'bookly' ),
            'verify_none' => __( 'OFF', 'bookly' ),
            'verify_phone' => __( 'Verify phone', 'bookly' ),
            'verify_email' => __( 'Verify email', 'bookly' ),
            'price' => __( 'Price', 'bookly' ),
            'show_blocked_slots' => __( 'Show blocked timeslots', 'bookly' ),
            'hidden_services' => __( 'Hidden services', 'bookly' ),
            'show_slot_info' => __( 'Show information about time slot', 'bookly' ),
            'available_dates' => __( 'Available dates in calendar', 'bookly' ),
            'available_dates_schedule' => __( 'Display dates according to schedule', 'bookly' ),
            'available_dates_with_slots' => __( 'Disable dates without available timeslots', 'bookly' ),
            'show_stepper' => __( 'Show stepper', 'bookly' ),
            'show_services_mode' => __( 'Cards with Any staff', 'bookly' ),
            'show_services_all' => __( 'Show', 'bookly' ),
            'show_services_no_any' => __( 'Don\'t show', 'bookly' ),
            'show_services_only_any' => __( 'Show only with Any staff', 'bookly' ),
            'show_staff_rating' => __( 'Show staff member rating before employee name', 'bookly' ),
            'skip_step' => __( 'Skip step', 'bookly' ),
            'initial_view' => __( 'Initial view', 'bookly' ),
            'initial_view_form' => __( 'Form', 'bookly' ),
            'initial_view_button' => __( 'Button', 'bookly' ),
            'initial_view_button_title' => __( 'Button title', 'bookly' ),
            'tags' => __( 'Tags', 'bookly' ),
            'tags_add_subsection' => __( 'Add subsection', 'bookly' ),
            'tags_section_title' => __( 'Section title', 'bookly' ),
            'tags_allow_skip' => __( 'Allow skip', 'bookly' ),
            'tags_skip_title' => __( 'Skip button title', 'bookly' ),
            'tags_skip_default_title' => __( 'Next', 'bookly' ),
            'page_url' => __( 'Page URL', 'bookly' ),
            'page_url_hint' => __( 'Enter the URL of the page where this form\'s shortcode is placed.', 'bookly' ),
        );

        foreach ( PaymentFlow::getSupportedGateways() as $gateway ) {
            $fields[ 'payment_system_' . $gateway ] = BooklyLib\Entities\Payment::typeToString( $gateway );
        }

        $staff = array();
        $rows = BooklyLib\Entities\Staff::query( 's' )->select( 's.id, s.full_name' )->sortBy( 's.position' )->fetchArray();
        foreach ( $rows as $row ) {
            $staff[] = array( 'id' => $row['id'], 'title' => BooklyLib\Utils\Common::getTranslatedString( 'staff_' . $row['id'], $row['full_name'] ) );
        }

        wp_localize_script( 'bookly-modern-appearance.js', 'BooklyL10nModernAppearance', BooklyModules\ModernBookingForm\Proxy\Shared::prepareAppearanceData( array(
            'qr_code' => plugins_url( 'backend/modules/appearance/resources/images/qr.png', Lib\Plugin::getMainFile() ),
            'name' => 'My form',
            'checkout_name' => 'Checkout form',
            'format_price' => BooklyLib\Utils\Price::formatOptions(),
            'moment_format_date' => BooklyLib\Utils\DateTime::convertFormat( 'date', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'moment_format_time' => BooklyLib\Utils\DateTime::convertFormat( 'time', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'duration' => BooklyLib\Utils\DateTime::secondsToInterval( HOUR_IN_SECONDS ),
            'appearance_url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ),
            'show_notice' => get_user_meta( get_current_user_id(), Lib\Plugin::getPrefix() . 'dismiss_modern_appearance_notice', true ) ? 0 : 1,
            'categories' => $categories,
            'services' => $services,
            'tags' => $tags,
            'tag_colors' => Lib\Config::getTagColors(),
            'gift_cards' => array(),
            'staff_categories' => $staff_categories,
            'staff' => $staff,
            'images' => plugins_url( 'frontend/resources/images/', BooklyLib\Plugin::getMainFile() ),
            'l10n' => array(
                'add_new_form' => __( 'Add new form', 'bookly' ),
                'back' => __( 'Back', 'bookly' ),
                'save' => __( 'Save', 'bookly' ),
                'general' => __( 'General', 'bookly' ),
                'error' => __( 'Error', 'bookly' ),
                'are_you_sure_delete' => __( 'Are you sure?', 'bookly' ),
                'are_you_sure_clone' => __( 'Are you sure?', 'bookly' ),
                'are_you_sure_slug' => __( 'Are you sure you want to change the slug? Changing the slug may lead to unexpected behavior.', 'bookly' ),
                'copy_shortcode' => __( 'Copy shortcode', 'bookly' ),
                'clone_form' => __( 'Clone form', 'bookly' ),
                'delete_form' => __( 'Delete form', 'bookly' ),
                'step_categories' => __( 'Categories', 'bookly' ),
                'step_tags' => __( 'Tags', 'bookly' ),
                'step_events' => __( 'Events', 'bookly' ),
                'step_tickets' => __( 'Tickets', 'bookly' ),
                'step_staff_categories' => __( 'Categories', 'bookly' ),
                'step_staff' => __( 'Staff', 'bookly' ),
                'step_services' => __( 'Services', 'bookly' ),
                'step_calendar' => __( 'Calendar', 'bookly' ),
                'step_extras' => __( 'Extras', 'bookly' ),
                'step_slots' => __( 'Time', 'bookly' ),
                'step_cart' => __( 'Cart', 'bookly' ),
                'step_details' => __( 'Details', 'bookly' ),
                'step_payment' => __( 'Payment', 'bookly' ),
                'step_complete' => __( 'Complete', 'bookly' ),
                'step_checkout' => __( 'Checkout', 'bookly' ),
                'step_checkout_complete' => __( 'Complete', 'bookly' ),
                'complete_success' => __( 'Success appointment booking', 'bookly' ),
                'complete_success_package' => __( 'Success package booking', 'bookly' ),
                'complete_error' => __( 'Error', 'bookly' ),
                'settings' => __( 'Settings', 'bookly' ),
                'step_settings' => __( 'Step settings', 'bookly' ),
                'custom_css' => __( 'Custom CSS', 'bookly' ),
                'save_to_apply' => __( 'Save the appearance to apply changes.', 'bookly' ),
                'saved' => __( 'Changes saved.', 'bookly' ),
                'service_card' => array(
                    'title' => 'Teeth Whitening',
                ),
                'service_card2' => array(
                    'title' => 'Crown and bridge',
                ),
                'package_card' => array(),
                'dropdown_texts' => array(
                    'selectAll' => __( 'Select all', 'bookly' ),
                    'allSelected' => __( 'All', 'bookly' ),
                    'nothingSelected' => __( 'Nothing selected', 'bookly' ),
                    'unknownSelected' => __( 'N/A', 'bookly' ),
                ),
                'help' => __( 'To learn more about this feature, please follow <a href="%s" target="_blank">this link</a>.', 'bookly' ),
                'notice' => __( 'How to publish this form on your web site?', 'bookly' ) .
                    '<br/>' . __( 'Select the form you want to publish, click on the menu button, and select \'Copy shortcode\'. Open the page where you want to add the booking form in a page edit mode and paste the previously copied shortcode. The form will be added to the page.', 'bookly' ) .
                    '<br/><a href="' . BooklyLib\Utils\Common::prepareUrlReferrers( 'https://support.booking-wp-plugin.com/hc/en-us/articles/212800185-Publish-Booking-Form', 'modern-appearance' ) . '" target="_blank">' . __( 'Read more', 'bookly' ) . '</a>',
            ),
            'details_fields' => array(
                'text' => __( 'Main text', 'bookly' ),
                'full_name' => __( 'Full name', 'bookly' ),
                'first_name' => __( 'First name', 'bookly' ),
                'last_name' => __( 'Last name', 'bookly' ),
                'email' => __( 'Email', 'bookly' ),
                'phone' => __( 'Phone', 'bookly' ),
                'address' => __( 'Address', 'bookly' ),
                'notes' => __( 'Notes', 'bookly' ),
                'birthday' => __( 'Birthday', 'bookly' ),
                'terms' => __( 'Terms & conditions', 'bookly' )
            ),
            'services_fields' => array( 'service' => __( 'Service title', 'bookly' ), 'duration' => __( 'Duration', 'bookly' ), 'price' => __( 'Price', 'bookly' ), 'staff' => __( 'Staff', 'bookly' ) ),
            'packages_fields' => array( 'package' => __( 'Package title', 'bookly' ), 'service' => __( 'Service title', 'bookly' ), 'duration' => __( 'Duration', 'bookly' ), 'price' => __( 'Price', 'bookly' ), 'staff' => __( 'Staff', 'bookly' ) ),
            'fields' => $fields,
            'payment_systems' => $payment_systems,
            'appearances' => $appearances,
        ) ) );

        return self::renderTemplate( 'index' );
    }

    /**
     * @param string $form_type
     * @return array
     */
    public static function getAppearance( $form_type = null, $token = null )
    {
        $appearance = array();
        if ( $token && $data = Lib\Entities\Form::query()->where( 'token', $token )->fetchRow() ) {
            $appearance = $data;
        }

        return self::prepareAppearanceSettings( $form_type, $appearance ?: null );
    }

    /**
     * @param string $form_type
     * @param array $db_appearance
     * @return array
     */
    public static function prepareAppearanceSettings( $form_type, $db_appearance )
    {
        switch ( $form_type ) {
            case Lib\Entities\Form::TYPE_SEARCH_FORM:
            case Lib\Entities\Form::TYPE_SERVICES_FORM:
            case Lib\Entities\Form::TYPE_STAFF_FORM:
            case Lib\Entities\Form::TYPE_TAGS_FORM:
            case Lib\Entities\Form::TYPE_EVENTS_FORM:
                $appearance = array(
                    'main_color' => '#F4662F',
                    'service_card_width' => 260,
                    'service_header_height' => 120,
                    'staff_card_width' => 260,
                    'hide_borders' => false,
                    'show_book_more' => true,
                    'show_qr_code' => true,
                    'show_calendar' => true,
                    'show_services_filter' => true,
                    'show_staff_filter' => true,
                    'show_staff_rating' => false,
                    'show_timezone' => false,
                    'show_receipt_on_cart' => true,
                    'show_receipt_on_payment' => true,
                    'skip_cart_step' => false,
                    'use_cart_local_storage' => true,
                    'default_service' => null,
                    'default_staff' => null,
                    'service_card_show_badge' => true,
                    'package_card_show_badge' => true,
                    'services_fields_order' => array( 'service', 'duration', 'price', 'staff' ),
                    'services_fields_show' => array( 'service', 'duration', 'price' ),
                    'packages_fields_order' => array( 'package', 'service', 'duration', 'price', 'staff' ),
                    'packages_fields_show' => array( 'package', 'service', 'duration', 'price' ),
                    'show_first_slot_only' => false,
                    'show_gift_cards' => $form_type !== Lib\Entities\Form::TYPE_EVENTS_FORM,
                    'show_add_to_calendar' => false,
                    'show_slot_info' => false,
                    'sell_services' => true,
                    'sell_gift_cards' => false,
                    'full_name_required' => true,
                    'first_name_required' => true,
                    'last_name_required' => true,
                    'email_required' => true,
                    'phone_required' => true,
                    'address_required' => false,
                    'birthday_required' => false,
                    'verify_credentials' => false,
                    'show_summary_service' => true,
                    'show_summary_staff' => true,
                    'show_summary_date' => true,
                    'show_summary_price' => true,
                    'show_blocked_slots' => false,
                    'show_change_date' => false,
                    'initial_view' => 'form',
                    'hidden_services' => array(),
                    'hidden_gift_cards' => array(),
                    'details_fields_order' => array( 'text', 'full_name', 'first_name', 'last_name', 'email', 'phone', 'google_maps', 'address', 'notes', 'birthday', 'custom_fields', 'terms' ),
                    'details_fields_width' => array( 'text' => 12, 'full_name' => 12, 'first_name' => 6, 'last_name' => 6, 'email' => 6, 'phone' => 6, 'google_maps' => 12, 'address' => 12, 'notes' => 12, 'birthday' => 12, 'terms' => 12, 'custom_fields' => array() ),
                    'details_fields_show' => array( 'text', 'first_name', 'last_name', 'email', 'phone', 'custom_fields' ),
                    'cards_display_mode' => 'with_slots',
                    'available_dates' => 'schedule',
                    'show_services_mode' => 'no_any',
                    'address' => array(
                        'order' => array( 'country', 'state', 'postcode', 'city', 'street', 'street_number', 'additional_address' ),
                        'show' => array( 'country', 'state', 'postcode', 'city', 'street', 'street_number', 'additional_address' ),
                        'labels' => array(
                            'country' => __( 'Country', 'bookly' ),
                            'state' => __( 'State/Region', 'bookly' ),
                            'postcode' => __( 'Postal Code', 'bookly' ),
                            'city' => __( 'City', 'bookly' ),
                            'street' => __( 'Street Address', 'bookly' ),
                            'street_number' => __( 'Street Number', 'bookly' ),
                            'additional_address' => __( 'Additional Address', 'bookly' ),
                        ),
                    ),
                    'l10n' => array(
                        'steps' => array(
                            'calendar' => __( 'Start', 'bookly' ),
                            'extras' => __( 'Extras', 'bookly' ),
                            'slots' => __( 'Time', 'bookly' ),
                            'cart' => __( 'Cart', 'bookly' ),
                            'details' => __( 'Details', 'bookly' ),
                            'payment' => __( 'Payment', 'bookly' ),
                            'done' => __( 'Finish', 'bookly' ),
                        ),
                        'steps_descriptions' => array(
                            'calendar' => __( 'Select service', 'bookly' ),
                            'extras' => __( 'Add extras', 'bookly' ),
                            'slots' => __( 'Select time slot', 'bookly' ),
                            'cart' => __( 'Check cart', 'bookly' ),
                            'details' => __( 'Your information', 'bookly' ),
                            'payment' => __( 'Make purchase', 'bookly' ),
                            'done' => __( 'Done', 'bookly' ),
                        ),
                        'address_label' => __( 'Address', 'bookly' ),
                        'address_placeholders' => array(
                            'country' => __( 'Country', 'bookly' ),
                            'state' => __( 'State/Region', 'bookly' ),
                            'postcode' => __( 'Postal Code', 'bookly' ),
                            'city' => __( 'City', 'bookly' ),
                            'street' => __( 'Street Address', 'bookly' ),
                            'street_number' => __( 'Street Number', 'bookly' ),
                            'additional_address' => __( 'Additional Address', 'bookly' ),
                        ),
                        'notes' => __( 'Notes', 'bookly' ),
                        'birthday' => __( 'Birthday', 'bookly' ),
                        'staff' => __( 'Staff', 'bookly' ),
                        'any_staff' => __( 'Any', 'bookly' ),
                        'service' => __( 'Service', 'bookly' ),
                        'date' => __( 'Date', 'bookly' ),
                        'time' => __( 'Time', 'bookly' ),
                        'price' => __( 'Price', 'bookly' ),
                        'next' => __( 'Next', 'bookly' ),
                        'back' => __( 'Back', 'bookly' ),
                        'select_service' => __( 'Select service', 'bookly' ),
                        'select_staff' => __( 'Any', 'bookly' ),
                        'book_now' => __( 'Book now', 'bookly' ),
                        'buy_now' => __( 'Buy now', 'bookly' ),
                        'book_more' => __( 'Book more', 'bookly' ),
                        'close' => __( 'Close', 'bookly' ),
                        'full_name' => __( 'Full name', 'bookly' ),
                        'full_name_error_required' => __( 'Required', 'bookly' ),
                        'first_name' => __( 'First name', 'bookly' ),
                        'first_name_error_required' => __( 'Required', 'bookly' ),
                        'last_name' => __( 'Last name', 'bookly' ),
                        'last_name_error_required' => __( 'Required', 'bookly' ),
                        'email' => __( 'Email', 'bookly' ),
                        'email_error_required' => __( 'Required', 'bookly' ),
                        'phone' => __( 'Phone', 'bookly' ),
                        'phone_error_required' => __( 'Required', 'bookly' ),
                        'address_field_required' => __( 'Required', 'bookly' ),
                        'birthday_error_required' => __( 'Required', 'bookly' ),
                        'no_slots' => __( 'No time slots available', 'bookly' ),
                        'slot_not_available' => __( 'Slot already booked', 'bookly' ),
                        'no_results' => __( 'No results found', 'bookly' ),
                        'nop' => __( 'Number of persons', 'bookly' ),
                        'booking_success' => __( 'Thank you!', 'bookly' ),
                        'booking_error' => __( 'Oops!', 'bookly' ),
                        'booking_completed' => __( 'Your booking is complete.', 'bookly' ),
                        'order_id' => __( 'Your order number', 'bookly' ),
                        'processing' => __( 'Your payment has been accepted for processing.', 'bookly' ),
                        'group_skip_payment' => __( 'Payment has been skipped.', 'bookly' ),
                        'payment_impossible' => __( 'No payment methods available. Please contact service provider.', 'bookly' ),
                        'appointments_limit_reached' => __( 'You are trying to use the service too often. Please contact us to make a booking.', 'bookly' ),
                        'terms_text' => __( 'I agree to the terms of service', 'bookly' ),
                        'text_calendar' => __( 'Please select a service', 'bookly' ),
                        'text_extras' => __( 'Select the Extras you\'d like (Multiple Selection)', 'bookly' ),
                        'text_slots' => __( 'Click on a time slot to proceed with booking', 'bookly' ),
                        'text_details' => __( 'Please provide your details in the form below to proceed with the booking', 'bookly' ),
                        'text_payment' => __( 'Please tell us how you would like to pay', 'bookly' ),
                        'timezone' => __( 'Your time zone', 'bookly' ),
                        'select_city' => __( 'Select city', 'bookly' ),
                        'add_to_calendar' => __( 'Add to calendar', 'bookly' ),
                        'total' => __( 'Total', 'bookly' ),
                        'sub_total' => __( 'Subtotal', 'bookly' ),
                        'discount' => __( 'Discount', 'bookly' ),
                        'to_pay' => __( 'Amount to pay', 'bookly' ),
                        'verify_code' => __( 'Verification code', 'bookly' ),
                        'verify_email' => __( 'Enter verification code from email', 'bookly' ),
                        'verify_phone' => __( 'Enter verification code from SMS', 'bookly' ),
                        'verify_resend' => __( 'Resend code', 'bookly' ),
                        'verify_apply' => __( 'Apply', 'bookly' ),
                        'verify_code_incorrect' => __( 'Incorrect verification code', 'bookly' ),
                        'cart_item_not_available' => __( 'The selected time is not available anymore', 'bookly' ),
                        'initial_view_button_title' => __( 'View', 'bookly' ),
                    ),
                );

                $appearance['type'] = $form_type;
                $appearance = BooklyModules\ModernBookingForm\Proxy\Shared::prepareAppearance( $appearance );

                if ( $form_type === Lib\Entities\Form::TYPE_TAGS_FORM ) {
                    $appearance['step_service_card_width'] = 260;
                    $appearance['step_service_card_header_height'] = 120;
                    $appearance['step_service_card_body_height'] = 240;
                    $appearance['step_tags_card_width'] = 260;
                    $appearance['step_tags_card_header_height'] = 120;
                    $appearance['step_tags_card_body_height'] = 240;
                    $appearance['services_list'] = null;
                    $appearance['l10n']['text_tags'] = __( 'Please select a category', 'bookly' );
                    $appearance['l10n']['text_tags_category'] = __( 'Section title', 'bookly' );
                    $appearance['l10n']['text_services'] = __( 'Please select a service', 'bookly' );
                    $appearance['skip_tags_step'] = false;
                    $appearance['skip_services_step'] = true;
                }
                if ( $form_type === Lib\Entities\Form::TYPE_SERVICES_FORM ) {
                    $appearance['category_card_width'] = 260;
                    $appearance['category_header_height'] = 120;
                    $appearance['category_body_height'] = 240;
                    $appearance['step_service_card_width'] = 260;
                    $appearance['step_service_card_header_height'] = 120;
                    $appearance['step_service_card_body_height'] = 240;
                    $appearance['categories_list'] = null;
                    $appearance['services_list'] = null;
                    $appearance['l10n']['categories'] = __( 'Categories', 'bookly' );
                    $appearance['l10n']['text_categories'] = __( 'Please select a category', 'bookly' );
                    $appearance['l10n']['text_services'] = __( 'Please select a service', 'bookly' );
                    $appearance['l10n']['more'] = __( '+%d more', 'bookly' );
                    $appearance['skip_categories_step'] = false;
                    $appearance['skip_services_step'] = true;
                }
                if ( $form_type === Lib\Entities\Form::TYPE_STAFF_FORM ) {
                    $appearance['category_card_width'] = 260;
                    $appearance['category_header_height'] = 120;
                    $appearance['category_body_height'] = 240;
                    $appearance['staff_card_width'] = 260;
                    $appearance['staff_card_header_height'] = 120;
                    $appearance['staff_card_body_height'] = 240;
                    $appearance['categories_list'] = null;
                    $appearance['staff_list'] = null;
                    $appearance['l10n']['categories'] = __( 'Categories', 'bookly' );
                    $appearance['l10n']['text_categories'] = __( 'Please select a category', 'bookly' );
                    $appearance['l10n']['text_staff'] = __( 'Please select a staff', 'bookly' );
                    $appearance['l10n']['more'] = __( '+%d more', 'bookly' );
                    $appearance['skip_staff_categories_step'] = true;
                    $appearance['skip_staff_step'] = false;
                }
                break;
            case Lib\Entities\Form::TYPE_CANCELLATION_FORM:
                $appearance = array(
                    'main_color' => '#F4662F',
                    'show_reason' => true,
                    'required_reason' => false,
                    'l10n' => array(
                        'text_cancellation' => __( 'Cancellation reason', 'bookly' ),
                        'text_do_not_cancel' => __( 'Thank you for being with us', 'bookly' ),
                        'confirm' => __( 'Confirm cancellation', 'bookly' ),
                        'cancel' => __( 'Do not cancel', 'bookly' ),
                        'required' => __( 'Required', 'bookly' ),
                        'error_header' => __( 'Oops!', 'bookly' ),
                        'error_cancelled' => __( 'This appointment has been already cancelled.', 'bookly' ),
                        'error_not_found' => __( 'This appointment does not exist.', 'bookly' ),
                        'error_not_allowed' => __( 'Cancellation of this appointment is not allowed.', 'bookly' ),
                    ),
                );
                break;
            case Lib\Entities\Form::TYPE_CHECKOUT_FORM:
                $appearance = array(
                    'main_color' => '#F4662F',
                );
                $appearance['l10n']['title'] = __( 'Checkout form', 'bookly' );
                $appearance['l10n']['amount'] = __( 'Amount', 'bookly' );
                $appearance['l10n']['select_payment_method'] = __( 'Please select a payment method', 'bookly' );
                $appearance['l10n']['payment_success'] = __( 'Thank you for your payment.', 'bookly' );
                $appearance['l10n']['payment_completed'] = __( 'Payment successful.', 'bookly' );
                $appearance['l10n']['payment_processing'] = __( 'Your payment has been accepted for processing.', 'bookly' );
                $appearance['l10n']['no_payment_required'] = __( 'There is no payment required.', 'bookly' );
                $appearance['l10n']['payment_not_found'] = __( 'Payment not found.', 'bookly' );
                $appearance['l10n']['error'] = __( 'Something went wrong.', 'bookly' );
                foreach ( PaymentFlow::getSupportedGateways() as $gateway ) {
                    if ( $gateway !== BooklyLib\Entities\Payment::TYPE_LOCAL ) {
                        $appearance[ 'checkout_gateway_' . $gateway ] = true;
                    }
                }
                break;
        }
        if ( $form_type !== Lib\Entities\Form::TYPE_CANCELLATION_FORM ) {
            foreach ( PaymentFlow::getSupportedGateways() as $gateway ) {
                switch ( $gateway ) {
                    case  BooklyLib\Entities\Payment::TYPE_LOCAL:
                        $title = __( 'I will pay locally', 'bookly' );
                        break;
                    default:
                        $title = Proxy\Shared::prepareGatewayTitle( __( 'I will pay now with Credit Card', 'bookly' ), $gateway );
                }
                $appearance['l10n'][ 'payment_system_' . $gateway ] = $title;
            }
        }
        if ( $db_appearance && isset( $db_appearance['settings'] ) && $db_appearance['settings'] ) {
            $settings = json_decode( $db_appearance['settings'], true );
            if ( isset( $appearance['details_fields_order'], $settings['details_fields_order'] ) && is_array( $settings['details_fields_order'] ) ) {
                foreach ( $appearance['details_fields_order'] as $item ) {
                    if ( ! in_array( $item, $settings['details_fields_order'], true ) ) {
                        $settings['details_fields_order'][] = $item;
                    }
                }
            }
            foreach ( $settings as $key => $value ) {
                if ( $key !== 'l10n' ) {
                    $appearance[ $key ] = $value;
                }
            }

            // Update appearance settings from old versions.
            if ( isset( $settings['show_address'] ) && $settings['show_address'] ) {
                $appearance['details_fields_show'][] = 'address';
                unset ( $settings['show_address'] );
            }
            if ( isset( $settings['show_notes'] ) && $settings['show_notes'] ) {
                $appearance['details_fields_show'][] = 'notes';
                unset ( $settings['show_notes'] );
            }
            if ( isset( $settings['show_terms'] ) && $settings['show_terms'] ) {
                $appearance['details_fields_show'][] = 'terms';
                unset ( $settings['show_terms'] );
            }
            unset ( $settings['l10n']['ca_notes'] );

            $appearance['custom_css'] = $db_appearance['custom_css'];
            $appearance['token'] = $db_appearance['token'];

            self::translateL10n( $settings['l10n'], $appearance['l10n'] );
        }

        return $appearance;
    }

    /**
     * @param array $strings
     * @param array $l10n
     */
    protected static function translateL10n( $strings, &$l10n )
    {
        foreach ( $strings as $key => $text ) {
            if ( is_array( $text ) ) {
                self::translateL10n( $text, $l10n[ $key ] );
            } else {
                $l10n[ $key ] = BooklyLib\Utils\Common::getTranslatedString( 'appearance_string_' . md5( $text ), $text );
            }
        }
    }
}