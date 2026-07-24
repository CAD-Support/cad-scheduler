<?php
namespace BooklyEvents\Backend\Components\Dialogs\Attendees;

use Bookly\Lib as BooklyLib;

class Dialog extends BooklyLib\Base\Component
{
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals', ),
        ) );
        self::enqueueScripts( array(
            'module' => array( 'js/event-attendees-dialog.js' => array( 'bookly-backend-globals' ), ),
        ) );

        wp_localize_script( 'bookly-event-attendees-dialog.js', 'BooklyL10nAttendeesDialog', array(
            'format_price' => BooklyLib\Utils\Price::formatOptions(),
            'l10n' => array(
                'add_attendee' => __( 'Add attendee', 'bookly' ),
                'add_ticket' => __( 'Add ticket', 'bookly' ),
                'and' => __( 'and', 'bookly' ),
                'any_orders' => __( 'Any orders', 'bookly' ),
                'are_you_sure' => __( 'Are you sure?', 'bookly' ),
                'attendees' => __( 'Attendees', 'bookly' ),
                'available' => __( 'Available', 'bookly' ),
                'back' => __( 'Back', 'bookly' ),
                'cancel' => __( 'Cancel', 'bookly' ),
                'check_in' => __( 'Check in', 'bookly' ),
                'checked_in' => __( 'Checked in', 'bookly' ),
                'clear' => __( 'Clear', 'bookly' ),
                'close' => __( 'Close', 'bookly' ),
                'create' => __( 'Create', 'bookly' ),
                'customer' => __( 'Customer', 'bookly' ),
                'delete' => __( 'Delete', 'bookly' ),
                'failed' => __( 'Failed', 'bookly' ),
                'delete_info' => __( 'This will permanently remove the ticket %s', 'bookly' ) . '.<br>' . __( 'This action cannot be undone.', 'bookly' ),
                'email' => __( 'Email', 'bookly' ),
                'event' => __( 'Event', 'bookly' ),
                'next' => __( 'Next', 'bookly' ),
                'no_attendees' => __( 'No attendees yet', 'bookly' ),
                'no_results' => __( 'No results found', 'bookly' ),
                'no_ticket_types' => __( 'Tickets are not yet available', 'bookly' ) . '. ' . __( 'Set up pricing and inventory to enable sales', 'bookly' ) . '.',
                'not_paid' => __( 'Not paid', 'bookly' ),
                'on_hold' => __( 'On hold', 'bookly' ),
                'order' => _x( 'Order', 'purchase order', 'bookly' ),
                'paid' => __( 'Paid', 'bookly' ),
                'phone' => __( 'Phone', 'bookly' ),
                'quantity' => __( 'Quantity', 'bookly' ),
                'reserved' => __( 'Reserved', 'bookly' ),
                'save' => __( 'Save', 'bookly' ),
                'search' => __( 'Search', 'bookly' ),
                'search_placeholder' => __( 'Search by name, code or order #', 'bookly' ),
                'select' => __( 'Select', 'bookly' ),
                'send_notifications' => __( 'Send notifications', 'bookly' ),
                'settings_saved' => __( 'Settings saved.', 'bookly' ),
                'sold' => __( 'Sold', 'bookly' ),
                'temporary_on_hold' => __( 'temporarily on hold by the payment system', 'bookly' ),
                'ticket' => __( 'Ticket', 'bookly' ),
                'tickets' => __( 'Tickets', 'bookly' ),
                'title' => __( 'Title', 'bookly' ),
                'total' => __( 'Total', 'bookly' ),
                'use_reserved_tickets' => __( 'Use reserved tickets', 'bookly' ),
                'customerSelector' => array(
                    'placeholder' => __( 'Search for customer', 'bookly' ),
                    'noResults' => __( 'No results found', 'bookly' ),
                    'searching' => __( 'Searching', 'bookly' ),
                    'new_customer' => __( 'New customer', 'bookly' ),
                    'more_results' => __( 'Refine your search to see more', 'bookly' ),
                ),
            ),
        ) );
    }
}