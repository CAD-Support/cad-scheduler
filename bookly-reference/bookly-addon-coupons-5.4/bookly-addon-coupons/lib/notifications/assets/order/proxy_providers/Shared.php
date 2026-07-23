<?php
namespace BooklyCoupons\Lib\Notifications\Assets\Order\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Notifications\Assets\Order\Codes;
use Bookly\Lib\Notifications\Assets\Order\Proxy;
use BooklyCoupons\Lib\Entities\Coupon;

abstract class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCodes( Codes $codes )
    {
        if ( $codes->getOrder()->hasPayment() && $codes->getOrder()->getPayment()->getCouponId() ) {
            $codes->coupon = Coupon::query()->where( 'id', $codes->getOrder()->getPayment()->getCouponId() )->fetchVar( 'code' );
        } else {
            $codes->coupon = '';
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareReplaceCodes( array $replace_codes, Codes $codes, $format )
    {
        $replace_codes['coupon'] = $codes->coupon;

        return $replace_codes;
    }
}