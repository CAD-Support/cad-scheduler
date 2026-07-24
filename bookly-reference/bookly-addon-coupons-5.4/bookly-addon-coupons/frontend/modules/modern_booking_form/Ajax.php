<?php
namespace BooklyCoupons\Frontend\Modules\ModernBookingForm;

use Bookly\Lib as BooklyLib;
use BooklyCoupons\Lib;
use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\Request;

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
    public static function modernBookingFormVerifyCoupon()
    {
        $request = new Request();

        $coupon = Lib\Utils\Common::checkCoupon( self::parameter( 'coupon' ), $request->getUserData() );

        if ( $coupon['success'] === true ) {
            wp_send_json_success( array(
                'coupon' => array(
                    'discount' => $coupon['data']['coupon']->getDiscount(),
                    'deduction' => $coupon['data']['coupon']->getDeduction(),
                    'service_id' => Lib\Entities\CouponService::query()->where( 'coupon_id', $coupon['data']['coupon']->getId() )->fetchCol( 'service_id' ),
                    'staff_id' => Lib\Entities\CouponStaff::query()->where( 'coupon_id', $coupon['data']['coupon']->getId() )->fetchCol( 'staff_id' ),
                ),
            ) );
        } else {
            wp_send_json( $coupon );
        }
    }
}