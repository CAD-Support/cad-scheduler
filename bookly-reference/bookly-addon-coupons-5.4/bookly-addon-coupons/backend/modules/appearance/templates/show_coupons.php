<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Inputs;
?>
<div class="col-md-3 my-2">
    <?php Inputs::renderCheckBox( __( 'Show coupons', 'bookly' ), null, get_option( 'bookly_coupons_enabled' ), array( 'id' => 'bookly-show-coupons' ) ) ?>
</div>