<?php
namespace BooklyEvents\Backend\Modules\Events;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib as BooklyProLib;
use Bookly\Lib\Utils;

class Page extends BooklyLib\Base\Component
{
    /**
     * Render page.
     */
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals' ),
        ) );

        self::enqueueScripts( array(
            'module' => array(
                'js/events.js' => array( 'bookly-backend-globals', 'bookly-queue-dialog.js' ),
            ),
        ) );

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::EVENTS );

        wp_localize_script( 'bookly-events.js', 'BooklyEventsL10n', array(
            'new_event'   => __( 'New event', 'bookly' ) . '…',
            'datePicker'  => Utils\DateTime::datePickerOptions(),
            'dateRange'   => Utils\DateTime::dateRangeOptions(),
            'zeroRecords' => __( 'No events for selected period and criteria.', 'bookly' ),
            'tickets'     => __( 'Tickets', 'bookly' ),
            'attendees'   => __( 'Attendees', 'bookly' ),
            'edit'        => __( 'Edit', 'bookly' ),
            'delete'      => __( 'Delete', 'bookly' ),
            'search'      => __( 'Quick search by ID, title, tags', 'bookly' ) . '…',
            'cancel'      => __( 'Cancel', 'bookly' ),
            'yes'         => __( 'Yes', 'bookly' ),
            'filters'     => array(
                'date'   => __( 'Date', 'bookly' ),
                'status' => __( 'Status', 'bookly' ),
            ),
            'status'      => array(
                'published' => __( 'Published', 'bookly' ),
                'draft'     => __( 'Draft', 'bookly' ),
            ),
            'datatables'  => $datatables,
            'tagsData'    => BooklyLib\Proxy\Pro::getTagsData( BooklyProLib\Entities\Tag::TYPE_EVENT ),
        ) );

        self::renderTemplate( 'index', compact( 'datatables' ) );
    }
}
