<?php
namespace BooklyEvents\Backend\Components\Dialogs\TicketType;

use Bookly\Lib as BooklyLib;

class Dialog extends BooklyLib\Base\Component
{
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals', ),
        ) );
        self::enqueueScripts( array(
            'module' => array( 'js/event-ticket-type-dialog.js' => array( 'bookly-backend-globals' ), ),
        ) );

        wp_localize_script( 'bookly-event-ticket-type-dialog.js', 'BooklyL10nTicketTypeDialog', array(
            'l10n' => array(
                'add_ticket' => __( 'Add ticket', 'bookly' ),
                'cancel' => __( 'Cancel', 'bookly' ),
                'click_to_create' => __( 'No tickets added yet', 'bookly' ) . '. ' . sprintf( __( 'Click "%s" to create your first one', 'bookly' ) . '.', __( 'Add ticket', 'bookly' ) ),
                'delete' => __( 'Delete', 'bookly' ),
                'event' => __( 'Event', 'bookly' ),
                'price' => __( 'Price', 'bookly' ),
                'quantity' => __( 'Quantity', 'bookly' ),
                'reserved' => __( 'Reserved', 'bookly' ),
                'save' => __( 'Save', 'bookly' ),
                'settings_saved' => __( 'Settings saved.', 'bookly' ),
                'sold' => __( 'Sold', 'bookly' ),
                'temporary_on_hold' => __( 'temporarily on hold by the payment system', 'bookly' ),
                'tickets' => __( 'Tickets', 'bookly' ),
                'title' => __( 'Title', 'bookly' ),
                'reorder' => _x( 'Reorder', 'order of elements', 'bookly' ),
            ),
        ) );
    }
}