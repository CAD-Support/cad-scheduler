<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Settings\Selects;
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Controls\Inputs as ControlsInputs;
use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Lib as BooklyLib;

?>
<div class="tab-pane" id="bookly_settings_additional">
    <form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'additional' ) ) ?>">
        <div class="card-body">
            <div class="form-group">
                <?php Selects::renderSingle( 'bookly_cal_frontend_enabled', __( 'Display front-end calendar', 'bookly' ), __( 'If this option is enabled then customers will be able to view the availability of the selected staff in a front-end calendar', 'bookly' ) ) ?>
                <?php Selects::renderSingle( 'bookly_cal_frontend_display_only_busy_slots', __( 'Display only busy slots', 'bookly' ), __( 'If this option is enabled then the front-end calendar will only show appointments with busy status (pending or approved). Free/available slots will not be displayed.', 'bookly' ) ) ?>
            </div>
            <?php if ( BooklyLib\Config::collaborativeServicesActive() || BooklyLib\Config::compoundServicesActive() ) : ?>
                <div class="form-group">
                    <?php Selects::renderSingle( 'bookly_combined_price_method', __( 'Price calculation method for combined services', 'bookly' ), __( 'This setting applies only to combined services such as collaborative or compound services. Select \'Regular price\' to use a fixed price for the service. Select \'Price based on nested services\' to calculate the service price as the sum of the prices of its nested services.', 'bookly' ), array( array( 'regular', __( 'Regular price', 'bookly' ) ), array( 'nested', __( 'Price based on nested services', 'bookly' ) ) ) ) ?>
                </div>
            <?php endif ?>
            <?php Proxy\Shared::renderAdditionalSettings() ?>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-end">
            <?php ControlsInputs::renderCsrf() ?>
            <?php Buttons::renderSubmit() ?>
            <?php Buttons::renderReset( null, 'ml-2' ) ?>
        </div>
    </form>
</div>