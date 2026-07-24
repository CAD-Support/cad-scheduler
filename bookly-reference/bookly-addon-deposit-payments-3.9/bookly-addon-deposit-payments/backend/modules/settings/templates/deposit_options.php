<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<div class="form-group">
    <label for="bookly_deposit_allow_full_payment"><?php echo esc_html_x( 'Deposit options', 'portion of the payment', 'bookly' ) ?></label>
    <select class="form-control custom-select" id="bookly_deposit_allow_full_payment" name="bookly_deposit_allow_full_payment">
        <?php foreach ( array( _x( 'Deposit only', 'portion of the payment', 'bookly' ) => 0, __( 'Deposit or full price (deposit by default)', 'bookly' ) => 1, __( 'Full price or deposit (full price by default)', 'bookly' ) => 2 ) as $text => $mode ) : ?>
            <option value="<?php echo esc_attr( $mode ) ?>" <?php selected( get_option( 'bookly_deposit_allow_full_payment' ), $mode ) ?> ><?php echo $text ?></option>
        <?php endforeach ?>
    </select>
    <small class="form-text text-muted"><?php esc_html_e( 'If you enable "Deposit only", customers will be requested to pay only a deposit amount. If you enable "Deposit or full price", customers will be requested to pay a deposit amount or a full amount.', 'bookly' ) ?></small>
</div>
