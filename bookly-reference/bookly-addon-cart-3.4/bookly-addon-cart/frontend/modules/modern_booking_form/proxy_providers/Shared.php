<?php
namespace BooklyCart\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyCart\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function prepareAppearance( array $bookly_options )
    {
        $bookly_options['l10n']['added_to_cart'] = __( 'Added to cart', 'bookly' );
        $bookly_options['l10n']['cart_empty'] = __( 'Cart is empty', 'bookly' );
        $bookly_options['l10n']['cart_items_error'] = __( 'Oops, it looks like some items in your cart are no longer available', 'bookly' );
        $bookly_options['l10n']['item_count'] = __( 'Item count', 'bookly' );
        $bookly_options['l10n']['text_cart'] = __( 'Below you can find a list of items in your cart', 'bookly' );

        return $bookly_options;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearanceData( array $bookly_options )
    {
        $bookly_options['fields']['cart_items_error'] = __( 'Some items are no longer available', 'bookly' );
        $bookly_options['fields']['cart_empty'] = __( 'Cart is empty', 'bookly' );
        $bookly_options['fields']['item_count'] = __( 'Item count', 'bookly' );
        $bookly_options['fields']['added_to_cart'] = __( 'Added to cart', 'bookly' );
        $bookly_options['fields']['skip_cart_step'] = __( 'Hide cart step', 'bookly' );

        return $bookly_options;
    }
}