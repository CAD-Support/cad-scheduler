<?php
namespace BooklyCoupons\Lib\Utils;

use BooklyCoupons\Lib;
use Bookly\Lib as BooklyLib;

abstract class Common
{
    const ERROR_INVALID = 'invalid';
    const ERROR_EXPIRED = 'expired';

    /**
     * Check coupon code
     *
     * @param string $coupon_code
     * @param BooklyLib\UserBookingData $userData
     * @return array
     */
    public static function checkCoupon( $coupon_code, $userData )
    {
        $coupon = new Lib\Entities\Coupon();
        $coupon->loadBy( array(
            'code' => $coupon_code,
        ) );

        if ( $coupon->isLoaded() && ! $coupon->fullyUsed() ) {
            // Check start date.
            if ( $coupon->started() ) {
                // Check end date.
                if ( ! $coupon->expired() ) {
                    // Check customer.
                    $customer = $userData->getCustomer();
                    if ( ( ! $customer->isLoaded() && ! $coupon->hasLimitForCustomer() ) || $coupon->validForCustomer( $customer ) ) {
                        // Check cart.
                        if ( $coupon->validForCart( $userData->cart ) ) {
                            // Coupon is valid.
                            return array( 'success' => true, 'data' => compact( 'coupon' ) );
                        }
                    }
                } else {
                    return array( 'success' => false, 'data' => array( 'error' => self::ERROR_EXPIRED ) );
                }
            }
        }

        return array( 'success' => false, 'data' => array( 'error' => self::ERROR_INVALID ) );
    }
}