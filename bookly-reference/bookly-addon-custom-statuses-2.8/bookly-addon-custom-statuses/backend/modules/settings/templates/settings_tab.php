<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
?>
<div class="tab-pane" id="bookly_settings_custom_statuses">
    <div class="card-body">
        <div class="bookly-css-root">
            <h5 class="bookly:text-base bookly:font-semibold bookly:mb-3"><?php esc_html_e( 'Custom statuses', 'bookly' ) ?></h5>
            <div id="bookly-custom_statuses-datatables"></div>
        </div>
    </div>

    <?php include '_modal.php' ?>
</div>
