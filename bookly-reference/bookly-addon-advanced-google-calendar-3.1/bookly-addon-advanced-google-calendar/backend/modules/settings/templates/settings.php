<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly

use Bookly\Backend\Components\Settings\Inputs;
use Bookly\Backend\Components\Settings\Selects;
?>
<?php Selects::renderSingle(
    'bookly_gc_sync_mode',
    __( 'Synchronization mode', 'bookly' ),
    __( 'With "One-way" sync Bookly pushes new appointments and any further changes to Google Calendar. With "Two-way front-end only" sync Bookly will additionally fetch events from Google Calendar and remove corresponding time slots before displaying the Time step of the booking form (this may lead to a delay when users click Next to get to the Time step). With "Two-way" sync all bookings created in Bookly Calendar will be automatically copied to Google Calendar and vice versa. Important: your website must use HTTPS. Google Calendar API will be able to send notifications only if there is a valid SSL certificate installed on your web server.', 'bookly' ),
    array(
        array( '1-way', __( 'One-way', 'bookly' ) ),
        array( '1.5-way', __( 'Two-way front-end only', 'bookly' ) ),
        array( '2-way', __( 'Two-way', 'bookly' ) )
    )
) ?>
<div class="border-left ml-4 pl-3">
<?php
    Inputs::renderNumbers(
        array( 'bookly_gc_full_sync_offset_days_before', 'bookly_gc_full_sync_offset_days_after', ),
        __( 'Sync appointments history (past and future)', 'bookly' ),
        __( 'Specify how many days of past and future calendar data you wish to sync at the time of initial sync. If you enter 0 in either field, synchronization of past or future events will not be performed', 'bookly' ),
        array( 0, 0, )
    );

    Selects::renderSingle(
        'bookly_gc_full_sync_titles',
        __( 'Copy Google Calendar event titles', 'bookly' ),
        __( 'If enabled then titles of Google Calendar events will be copied to Bookly appointments. If disabled, a standard title "Google Calendar event" will be used.', 'bookly' )
    );

    Selects::renderSingle(
        'bookly_gc_force_update_description',
        __( 'Overwrite the description of the original Google Calendar events', 'bookly' ),
        __( 'If enabled then the description of the Google Calendar events originally copied to Bookly will be overwritten by the Bookly appointment data after they have been edited in Bookly Calendar', 'bookly' )
    );
?>
</div>
