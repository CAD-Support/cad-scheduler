<?php
namespace BooklyCoupons\Frontend\Modules\Booking;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Components\Booking\InfoText as BookingComponents;
use Bookly\Frontend\Modules\Booking\Lib\Steps;
use Bookly\Frontend\Modules\Booking\Lib\Errors;
use BooklyCoupons\Lib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Apply coupon
     */
    public static function applyCoupon()
    {
        $userData = new BooklyLib\UserBookingData( self::parameter( 'form_id' ) );

        if ( $userData->load() ) {
            $coupon_code = self::parameter( 'coupon_code' );

            $coupon = new Lib\Entities\Coupon();
            $coupon->loadBy( array(
                'code' => $coupon_code,
            ) );

            $info_text_coupon_tpl = BooklyLib\Utils\Common::getTranslatedOption(
                BooklyLib\Config::showStepCart() && count( $userData->cart->getItems() ) > 1
                    ? 'bookly_l10n_info_coupon_several_apps'
                    : 'bookly_l10n_info_coupon_single_app'
            );

            $coupon = Lib\Utils\Common::checkCoupon( $coupon_code, $userData );

            if ( $coupon['success'] === true ) {
                $userData->setCouponCode( $coupon_code );
                $response = array( 'success' => true );
            } else {
                $userData->setCouponCode( null );
                $response = array(
                    'success' => false,
                    'error' => $coupon['data']['error'] === Lib\Utils\Common::ERROR_INVALID
                        ? BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_coupon_error_invalid' )
                        : BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_coupon_error_expired' ),
                    'text' => BookingComponents::prepare( Steps::PAYMENT, $info_text_coupon_tpl, $userData ),
                );
            }

            $userData->sessionSave();

            // Output JSON response.
            wp_send_json( $response );
        }

        Errors::sendSessionError();
    }
}