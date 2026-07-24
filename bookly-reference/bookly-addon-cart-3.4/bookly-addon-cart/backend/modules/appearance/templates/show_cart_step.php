<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Inputs;
?>
<div class="col-lg-4 col-xl-3 mb-2">
    <?php Inputs::renderCheckBox( __( 'Show Cart step', 'bookly' ), null, get_option( 'bookly_cart_enabled' ), array( 'id' => 'bookly-show-step-cart', 'data-target' => 'bookly-step-5', 'data-type' => 'bookly-show-step-checkbox' ) ) ?>
</div>