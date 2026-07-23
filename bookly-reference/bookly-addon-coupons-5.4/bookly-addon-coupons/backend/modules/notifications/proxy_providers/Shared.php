<?php
namespace BooklyCoupons\Backend\Modules\Notifications\ProxyProviders;

use Bookly\Backend\Modules\Notifications\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareNotificationCodes( array $codes, $type )
    {
        $codes['payment']['coupon'] = array( 'description' => __( 'Coupon code', 'bookly' ) );

        return $codes;
    }
}