<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Settings\Selects;
?>
<div class="card bookly-collapse-with-arrow">
    <div class="card-header d-flex align-items-center">
        <a href="#bookly_add-collaborative_services" class="ml-2 bookly-collapsed" role="button" data-toggle="bookly-collapse" aria-expanded="false">
            <?php _e( 'Collaborative services', 'bookly' ) ?>
        </a>
    </div>
    <div id="bookly_add-collaborative_services" class="bookly-collapse">
        <div class="card-body pb-0">
            <?php Selects::renderSingle( 'bookly_collaborative_hide_staff', __( 'Do not allow to select a specific staff member', 'bookly' ), __( 'If this option is enabled then customers won\'t be able to select a staff member for collaborative services in the first step of the booking form', 'bookly' ) ) ?>
        </div>
    </div>
</div>