<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<div class="col-3">
    <div class="d-lg-none"><?php echo esc_html_x( 'Deposit', 'portion of the payment', 'bookly' ) ?></div>
    <input class="form-control text-right" type="text" <?php echo $attribute ?> name="deposit[<?php echo $service->getId() ?>]" value="<?php echo esc_attr( $value ) ?>" <?php if ( $read_only ) : ?>readonly<?php endif ?>/>
</div>