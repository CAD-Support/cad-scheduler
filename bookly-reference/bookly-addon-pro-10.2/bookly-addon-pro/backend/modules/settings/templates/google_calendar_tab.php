<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Controls\Inputs as ControlsInputs;
use Bookly\Backend\Components\Settings\Inputs;
use Bookly\Backend\Components\Settings\Selects;
use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Backend\Modules\Settings\Codes;
use Bookly\Backend\Components\Ace;
use Bookly\Lib\Config;
use BooklyPro\Lib\Google;
?>
<div class="tab-pane" id="bookly_settings_google_calendar">
    <form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'google_calendar' ) ) ?>">
        <div class="card-body">
            <div class="form-group">
                <h4><?php esc_html_e( 'Instructions', 'bookly' ) ?></h4>
                <p><?php printf( __( 'To setup %s integration, follow the instruction in <a href="%s" target="_blank">our documentation</a>', 'bookly' ), 'Google Calendar', 'https://hub.bookly.pro/go/bookly-settings-google-calendar' ) ?></p>
            </div>
            <?php Inputs::renderText( 'bookly_gc_client_id', __( 'Client ID', 'bookly' ), __( 'The client ID obtained from the Google Cloud Platform', 'bookly' ) ) ?>
            <?php Inputs::renderText( 'bookly_gc_client_secret', __( 'Client secret', 'bookly' ), __( 'The client secret obtained from the Google Cloud Platform', 'bookly' ) ) ?>
            <?php Inputs::renderTextCopy( Google\Client::generateRedirectURI(), __( 'Redirect URI', 'bookly' ), __( 'Enter this URL as a redirect URI in the Google Cloud Platform', 'bookly' ) ) ?>
            <?php if ( Config::advancedGoogleCalendarActive() ) : ?>
                <?php Proxy\AdvancedGoogleCalendar::renderSettings() ?>
            <?php else : ?>
                <?php Selects::renderSingle( 'bookly_gc_sync_mode', __( 'Synchronization mode', 'bookly' ), sprintf( __( 'With "One-way" sync Bookly pushes new appointments and any further changes to %s.', 'bookly' ), __( 'Google Calendar', 'bookly' ) ) . ' ' . sprintf( __( 'With "Two-way front-end only" sync Bookly will additionally fetch events from %s and remove corresponding time slots before displaying the Time step of the booking form (this may lead to a delay when users click Next to get to the Time step).', 'bookly' ), __( 'Google Calendar', 'bookly' ) ), array(
                    array( '1-way', __( 'One-way', 'bookly' ) ),
                    array( '1.5-way', __( 'Two-way front-end only', 'bookly' ) ),
                ) ) ?>
            <?php endif ?>
            <div class="border-left ml-4 pl-3">
                <?php Selects::renderSingle( 'bookly_gc_limit_events', __( 'Limit number of fetched events', 'bookly' ), __( 'If there is a lot of events in Google Calendar sometimes this leads to a lack of memory in PHP when Bookly tries to fetch all events. You can limit the number of fetched events here.', 'bookly' ), $fetch_limits ) ?>
            </div>
            <?php Inputs::renderText( 'bookly_gc_event_title', __( 'Template for event title', 'bookly' ), __( 'Configure what information should be placed in the title of Google Calendar event.', 'bookly' ) . ' ' . sprintf( __('Available codes are %s and %s.', 'bookly' ), '{service_name}, {category_name}, {staff_name}, {client_names}', '{client_phones}' ) ) ?>
            <div class="form-group">
                <label for="bookly_gc_event_description"><?php esc_html_e( 'Template for event description', 'bookly' ) ?></label>
                <?php Ace\Editor::render( 'bookly-settings-google-calendar', 'bookly_gc_event_description', Codes::getJson( 'google_calendar' ), get_option( 'bookly_gc_event_description', '' ) ) ?>
                <input type="hidden" name="bookly_gc_event_description" value="<?php echo esc_attr( get_option( 'bookly_gc_event_description', '' ) ) ?>">
            </div>
        </div>

        <div class="card-footer bg-transparent d-flex justify-content-end">
            <?php ControlsInputs::renderCsrf() ?>
            <?php Buttons::renderSubmit() ?>
            <?php Buttons::renderReset( null, 'ml-2' ) ?>
        </div>
    </form>
</div>