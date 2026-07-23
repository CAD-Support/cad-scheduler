<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Modules\Appearance\Codes;
use Bookly\Backend\Components\Editable\Elements;
?>
<div class="bookly-js-payment-coupons"<?php if ( ! get_option( 'bookly_coupons_enabled' ) ) : ?> style="display: none;"<?php endif ?>>
    <div class="bookly-box bookly-js-payment-single-app">
        <?php Elements::renderText( 'bookly_l10n_info_coupon_single_app', Codes::getJson( 7 ) ) ?>
    </div>
    <div class="bookly-box bookly-js-payment-several-apps">
        <?php Elements::renderText( 'bookly_l10n_info_coupon_several_apps', Codes::getJson( 7, true ) ) ?>
    </div>

    <div class="bookly-box bookly-list">
        <?php Elements::renderString( array( 'bookly_l10n_label_coupon', 'bookly_l10n_coupon_error_invalid', 'bookly_l10n_coupon_error_expired' ) ) ?>
        <div class="bookly-inline-block">
            <input class="bookly-user-coupon" type="text"/>
            <div class="bookly-btn bookly-inline-block">
                <?php Elements::renderString( array( 'bookly_l10n_button_apply', ) ) ?>
            </div>
        </div>
    </div>
</div>