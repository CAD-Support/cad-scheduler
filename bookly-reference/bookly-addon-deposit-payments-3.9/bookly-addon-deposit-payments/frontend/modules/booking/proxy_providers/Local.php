<?php
namespace BooklyDepositPayments\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Proxy\DepositPayments as DepositPaymentsProxy;
use Bookly\Frontend\Components\Booking\InfoText;

class Local extends DepositPaymentsProxy
{
    /**
     * Render pay now row.
     *
     * @param BooklyLib\CartInfo $cart_info
     * @param array              $table = ['headers' => [], 'header_position' => [], 'show' => [] ]
     * @param string             $layout
     */
    public static function renderPayNowRow( BooklyLib\CartInfo $cart_info, array $table, $layout )
    {
        if ( $layout == 'mobile' ) {
            self::renderTemplate( 'cart_mobile', compact( 'table', 'cart_info' ) );
        } else {
            self::renderTemplate( 'cart_desktop', compact( 'table', 'cart_info' ) );
        }
    }

    /**
     * Render payment step selector deposit/full payment
     *
     * @param BooklyLib\UserBookingData $userData
     */
    public static function renderPaymentStep( BooklyLib\UserBookingData $userData )
    {
        if ( get_option( 'bookly_deposit_allow_full_payment' ) ) {
            $cart_info = $userData->cart->getInfo();
            if ( $cart_info->getTotal() > $cart_info->getDepositPay() ) {
                $info_text_deposit = InfoText::prepare( 7, BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_info_deposit' ), $userData );
                self::renderTemplate( '_7_payment', compact( 'userData', 'cart_info', 'info_text_deposit' ) );
            }
        }
    }
}