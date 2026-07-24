<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Settings\Inputs;
?>
<div class="card bookly-collapse-with-arrow">
    <div class="card-header d-flex align-items-center">
        <a href="#bookly_add-events" class="ml-2 bookly-collapsed" role="button" data-toggle="bookly-collapse" aria-expanded="false">
            <?php _e( 'Events', 'bookly' ) ?>
        </a>
    </div>
    <div id="bookly_add-events" class="bookly-collapse">
        <div class="card-body pb-0">
            <?php Inputs::renderText( 'bookly_event_ticked_default_code_mask', __( 'Default ticket mask', 'bookly' ), __( 'Enter a mask containing asterisks "*" for variables.', 'bookly' ) ) ?>
        </div>
    </div>
</div>