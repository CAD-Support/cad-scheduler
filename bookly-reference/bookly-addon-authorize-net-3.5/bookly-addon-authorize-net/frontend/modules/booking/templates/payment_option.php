<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/** @var Bookly\Lib\CartInfo $cart_info */
use Bookly\Lib\Utils;
?>
<div class="bookly-box bookly-list">
    <label>
        <input type="radio" class="bookly-js-payment" name="payment-method-<?php echo $form_id ?>" value="card" data-gateway="authorize_net" data-with-details="1"/>
        <span><?php echo Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_authorize_net' ) ?>
            <?php if ( $show_price ) : ?>
                <span class="bookly-js-pay"><?php echo Utils\Price::format( $cart_info->getPayNow() ) ?></span>
            <?php endif ?>
        </span>
        <img src="<?php echo $url_cards_image ?>" alt="cards" />
    </label>
    <form class="bookly-js-details" style="display: none; margin-top: 15px;">
        <?php Bookly\Frontend\Components\Booking\CardPayment::render() ?>
    </form>
</div>