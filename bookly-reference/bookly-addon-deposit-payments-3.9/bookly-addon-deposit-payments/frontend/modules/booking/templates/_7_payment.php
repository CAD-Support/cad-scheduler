<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/** @var Bookly\Lib\CartInfo $cart_info */
/** @var Bookly\Lib\UserBookingData $userData */
use Bookly\Lib\Utils\Common;
use Bookly\Lib as BooklyLib;
?>
<div class="bookly-js-deposit-wrap">
    <div class="bookly-box bookly-js-info-text-coupon"><?php echo $info_text_deposit ?></div>
    <div class="bookly-js-deposit">
        <div class="bookly-box bookly-list">
            <label>
                <span class="bookly-radio-loading" style="display: none;"></span>
                <input type="radio" class="bookly-js-deposit" name="bookly-full-payment" value="0" <?php checked( $userData->getDepositFull() == 0 ) ?>>
                <span><?php echo Common::getTranslatedOption( 'bookly_l10n_label_deposit_payment' ) ?> <b data-type="deposit_payment"><?php echo BooklyLib\Utils\Price::format( min( $cart_info->getDepositPay(), $cart_info->getTotal() ) ) ?></b></span>
            </label>
        </div>
        <div class="bookly-box bookly-list">
            <label>
                <span class="bookly-radio-loading" style="display: none;"></span>
                <input type="radio" class="bookly-js-deposit" name="bookly-full-payment" value="1" <?php checked( $userData->getDepositFull() == 1 ) ?>>
                <span><?php echo Common::getTranslatedOption( 'bookly_l10n_label_full_payment' ) ?> <b data-type="total_payment"><?php echo BooklyLib\Utils\Price::format( $cart_info->getTotal() ) ?></b></span>
            </label>
        </div>
    </div>
</div>