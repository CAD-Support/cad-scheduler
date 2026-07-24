<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<div class="form-group">
    <label for="bookly-service-deposit"><?php echo esc_html_x( 'Deposit', 'portion of the payment', 'bookly' ) ?></label>
    <input id="bookly-service-deposit" class="form-control" type="text" name="deposit" value="<?php echo esc_attr( $service['deposit'] ) ?>" />
</div>
