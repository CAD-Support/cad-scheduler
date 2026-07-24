<?php
namespace BooklyEvents\Backend\Components\Dialogs\Event;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Config;
use BooklyPro\Lib\Entities\Tag;

class Dialog extends BooklyLib\Base\Component
{
    public static function render()
    {
        global $wpdb;

        $wc = array(
            'enabled' => (int) ( get_option( 'bookly_wc_enabled' ) && get_option( 'bookly_wc_product' ) ),
            'products' => array( array( 'id' => '0', 'name' => __( 'Default', 'bookly' ) ) )
        );

        if ( $wc['enabled'] ) {
            $query = 'SELECT ID, post_title FROM ' . $wpdb->posts . ' WHERE post_type = \'product\' AND post_status = \'publish\' ORDER BY post_title';
            $products = $wpdb->get_results( $query );
            foreach ( $products as $product ) {
                $wc['products'][] = array( 'id' => $product->ID, 'name' => $product->post_title );
            }
        }

        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals', ),
        ) );
        wp_enqueue_media();
        self::enqueueScripts( array(
            'module' => array(
                'js/event-dialog.js' => array( 'bookly-backend-globals', 'media-views' ),
            ),
        ) );

        $staff = BooklyLib\Entities\Staff::query()
            ->select( 'id, full_name AS title' )
            ->whereNot( 'visibility', 'archive' )
            ->fetchArray();

        // Get tags
        $tags = Tag::query()
            ->select( 'id, tag, color_id' )
            ->where( 'type', Tag::TYPE_EVENT )
            ->sortBy( 'tag' )
            ->fetchArray();
        $tag_colors = (array) get_option( 'bookly_tag_colors', array() );

        $l10n_meeting = array(
            'copied' => __( 'copied', 'bookly' ),
            'copy_to_clipboard' => __( 'Copy to clipboard', 'bookly' ),
            'create_online_meetings' => __( 'Create online meetings', 'bookly' ),
            'create_online_meetings_help' => __( 'If this setting is enabled then online meetings will be created for new event bookings with the selected online meeting provider.', 'bookly' ) . ' ' . sprintf( __( 'Make sure that the provider is configured properly in Settings > <a href="%s">Online Meetings</a>', 'bookly' ), BooklyLib\Utils\Common::escAdminUrl( 'bookly-settings', array( 'tab' => 'online_meetings' ) ) ),
            'default' => __( 'Default', 'bookly' ),
            'meeting_code' => __( 'This link can be inserted into notifications with {online_meeting_start_url} code', 'bookly' ),
            'meeting_create' => __( 'Save event to create a meeting', 'bookly' ),
            'meeting_host' => __( 'Meeting host', 'bookly' ),
            'meeting_host_help' => __( 'Select the staff member who will be assigned as the meeting host', 'bookly' ),
            'online_meeting' => __( 'Online meeting', 'bookly' ),
            'please_configure_zoom' => sprintf( __( 'Please configure Zoom <a href="%s">settings</a> first', 'bookly' ), BooklyLib\Utils\Common::escAdminUrl( 'bookly-settings', array( 'tab' => 'online_meetings' ) ) ),
            'providers' => array(
                array( 'id' => 'off', 'name' => __( 'OFF', 'bookly' ) ),
                array( 'id' => 'zoom', 'name' => 'Zoom' ),
                // array( 'id' => 'google_meet', 'name' => 'Google Meet' ),
                array( 'id' => 'jitsi', 'name' => 'Jitsi Meet' ),
                array( 'id' => 'bbb', 'name' => 'BigBlueButton' ),
            )
        );

        wp_localize_script( 'bookly-event-dialog.js', 'BooklyL10nEventDialog', array(
            'datePicker' => BooklyLib\Utils\DateTime::datePickerOptions( array(
                'timeFormat' => BooklyLib\Utils\DateTime::convertFormat( 'time', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
                'timePicker24Hour' => (int) ! preg_match( '/\b[aA]\b/', get_option( 'time_format' ) ),
                'applyLabel' => __( 'Apply', 'bookly' ),
                'cancelLabel' => __( 'Cancel', 'bookly' ),
            ) ),
            'staff' => $staff,
            'locations' => BooklyLib\Proxy\Locations::getAll() ?: array(),
            'default_mask' => get_option( 'bookly_event_ticked_default_code_mask' ),
            'tagsList' => $tags,
            'tagColors' => $tag_colors,
            'wc' => $wc,
            'zoom_enabled' => (int) ( Config::zoomOAuthClientId() && Config::zoomOAuthClientSecret() && Config::zoomOAuthToken() ),
            'l10n' => array(
                'attendees' => __( 'Attendees', 'bookly' ),
                'cancel' => __( 'Cancel', 'bookly' ),
                'checked_in' => __( 'Checked in', 'bookly' ),
                'clear_field' => __( 'Clear field', 'bookly' ),
                'close' => __( 'Close', 'bookly' ),
                'color' => __( 'Color', 'bookly' ),
                'date' => __( 'Date', 'bookly' ),
                'delete' => __( 'Delete', 'bookly' ),
                'draft' => __( 'Draft', 'bookly' ),
                'edit' => __( 'Edit', 'bookly' ),
                'edit_event' => __( 'Edit event', 'bookly' ),
                'failed' => __( 'Failed', 'bookly' ),
                'hidden' => __( 'Hidden', 'bookly' ),
                'image' => __( 'Image', 'bookly' ),
                'choose_image' => __( 'Choose image', 'bookly' ),
                'remove_image' => __( 'Remove image', 'bookly' ),
                'info' => __( 'Info', 'bookly' ),
                'info_help' => sprintf( __( 'This text can be inserted into notifications with %s code', 'bookly' ), '{event_info}' ),
                'location' => __( 'Location', 'bookly' ),
                'location_placeholder' => __( 'Select location', 'bookly' ) . '…',
                'location_none' => __( 'No location', 'bookly' ),
                'max_capacity' => __( 'Max capacity', 'bookly' ),
                'max_capacity_help' => __( 'Total tickets across all ticket types', 'bookly' ) . '. ' . __( '0 - unlimited', 'bookly' ) . '.',
                'more' => __( 'More', 'bookly' ),
                'new_event' => __( 'New event', 'bookly' ),
                'organizers' => __( 'Organizers', 'bookly' ),
                'organizers_help' => __( 'Plan and manage the event from start to finish', 'bookly' ),
                'published' => __( 'Published', 'bookly' ),
                'required' => __( 'Required', 'bookly' ),
                'sales_end_at' => __( 'Ticket sales end at', 'bookly' ),
                'save' => __( 'Save', 'bookly' ),
                'create' => __( 'Create', 'bookly' ),
                'send_notifications' => __( 'Notification about new event', 'bookly' ),
                'settings_saved' => __( 'Settings saved.', 'bookly' ),
                'sold' => __( 'Sold', 'bookly' ),
                'status' => __( 'Status', 'bookly' ),
                'support_staff' => __( 'Support staff', 'bookly' ),
                'support_staff_help' => __( 'Assist with setup, operations, and attendee support during the event', 'bookly' ),
                'tags' => __( 'Tags', 'bookly' ),
                'add_tag' => __( 'Add tag', 'bookly' ) . '…',
                'ticket_mask' => __( 'Ticket mask', 'bookly' ),
                'ticket_mask_help' => __( 'Enter a mask containing asterisks "*" for variables.', 'bookly' ),
                'tickets' => __( 'Tickets', 'bookly' ),
                'title' => __( 'Title', 'bookly' ),
                'steps' => array(
                    'general' => array(
                        'title' => __( 'General', 'bookly' ),
                        'icon'  => 'fas fa-fw fa-cog mr-lg-1',
                    ),
                    'tickets' => array(
                        'title' => __( 'Tickets', 'bookly' ),
                        'icon'  => 'fas fa-fw fa-ticket-alt mr-lg-1',
                    ),
                    'advanced' => array(
                        'title' => __( 'Advanced', 'bookly' ),
                        'icon'  => 'fas fa-fw fa-cogs mr-lg-1',
                    ),
                ),
                'wc' => array(
                    'product' => 'WooCommerce ' . __( 'Booking product', 'bookly' ),
                    'item_name' => __( 'Cart item data', 'bookly' ),
                ),
                'dropdown_texts' => array(
                    'selectAll' => __( 'Select all', 'bookly' ),
                    'allSelected' => __( 'All', 'bookly' ),
                    'nothingSelected' => __( 'Nothing selected', 'bookly' ),
                    'unknownSelected' => __( 'N/A', 'bookly' ),
                ),
                'meeting' => $l10n_meeting,
                'datetime' => array(
                    'placeholder' => __( 'Select date', 'bookly' ) . '…',
                    'startTime' => __( 'Start time', 'bookly' ),
                    'endTime' => __( 'End time', 'bookly' ),
                    'time' => __( 'Time', 'bookly' ),
                    'apply' => __( 'Apply', 'bookly' ),
                    'cancel' => __( 'Cancel', 'bookly' ),
                ),
                'multiselect' => array(
                    'select_all' => __( 'Select all', 'bookly' ),
                    'all' => __( 'All', 'bookly' ),
                    'selected_n' => __( '%s selected', 'bookly' ),
                    'nothing' => __( 'Nothing selected', 'bookly' ),
                ),
            ),
        ) );
    }
}
