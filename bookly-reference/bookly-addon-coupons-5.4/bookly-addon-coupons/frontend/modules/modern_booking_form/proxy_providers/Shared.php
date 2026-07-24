<?php
namespace BooklyCoupons\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyCoupons\Lib;
use BooklyPro\Lib as BooklyProLib;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function prepareAppearance( array $bookly_options )
    {
        $bookly_options['show_coupons'] = $bookly_options['type'] !== BooklyProLib\Entities\Form::TYPE_EVENTS_FORM;
        $bookly_options['l10n']['coupon_text'] = __( 'Your coupon code if you have one', 'bookly' );
        $bookly_options['l10n']['coupon_button'] = __( 'Apply', 'bookly' );
        $bookly_options['l10n']['coupon_label'] = __( 'Coupon', 'bookly' );
        $bookly_options['l10n']['coupon_invalid'] = __( 'This coupon code is invalid or has been used', 'bookly' );
        $bookly_options['l10n']['coupon_expired'] = __( 'This coupon code has expired', 'bookly' );

        return $bookly_options;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearanceData( array $bookly_options )
    {
        $bookly_options['fields']['show_coupons'] = __( 'Show coupons', 'bookly' );

        return $bookly_options;
    }
}