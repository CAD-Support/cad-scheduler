<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Controls\Inputs as ControlsInputs;
use Bookly\Backend\Components\Settings\Inputs;
?>
<div class="tab-pane" id="bookly_settings_coupons">
    <form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'coupons' ) ) ?>">
        <div class="card-body">
            <?php Inputs::renderText( 'bookly_coupons_default_code_mask', __( 'Default code mask', 'bookly' ), __( 'Enter default mask for auto-generated codes.', 'bookly' ) ) ?>
        </div>

        <div class="card-footer bg-transparent d-flex justify-content-end">
            <?php ControlsInputs::renderCsrf() ?>
            <?php Buttons::renderSubmit() ?>
            <?php Buttons::renderReset( null, 'ml-2' ) ?>
        </div>
    </form>
</div>