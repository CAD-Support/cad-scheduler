<?php
namespace BooklyCoupons\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;

class Local extends Proxy\Coupons
{
    /**
     * @inheritDoc
     */
    public static function renderCouponBlock()
    {
        self::renderTemplate( 'coupon_block' );
    }
    /**
     * @inheritDoc
     */
    public static function renderShowCoupons()
    {
        self::renderTemplate( 'show_coupons' );
    }
}