<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Inputs;
?>
<div class="col-md-3 my-2">
    <?php Inputs::renderCheckBox( __( 'Show chain appointments', 'bookly' ), null, get_option( 'bookly_chain_appointments_enabled' ), array( 'id' => 'bookly-show-chain-appointments' ) ) ?>
</div>