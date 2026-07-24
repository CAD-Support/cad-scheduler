<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<div class="bookly-box bookly-info-text-coupon"><?php echo $info_text_coupon ?></div>
<div class="bookly-box bookly-list">
    <?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_coupon' ) ?>
    <?php if ( $coupon_code ) : ?>
        <?php echo esc_attr( $coupon_code ) . ' ✓' ?>
    <?php else : ?>
        <input class="bookly-user-coupon" name="bookly-coupon" type="text" value="<?php echo esc_attr( $coupon_code ) ?>"/>
        <button class="bookly-btn ladda-button bookly-inline-block bookly-js-apply-coupon" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_apply' ) ?></span><span class="spinner"></span>
        </button>
    <?php endif ?>
</div>