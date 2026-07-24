<?php
namespace BooklyDiscounts\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyDiscounts\Lib;
use Bookly\Frontend\Modules\Booking\Proxy;

class Local extends Proxy\Discounts
{
    /**
     * Render "Discount" row on a Cart step.
     *
     * @param array $table = ['headers' => [], 'header_position' => [], 'show' => [] ]
     * @param string $layout
     * @param BooklyLib\UserBookingData $userData
     */
    public static function renderCartDiscountRow( array $table, $layout, $userData )
    {
        if ( isset( $table['header_position']['price'] ) ) {
            $discounts = Lib\Utils\Common::getCartDiscounts( $userData );
            if ( ! empty( $discounts ) ) {
                self::renderTemplate( 'cart_discount', compact( 'discounts', 'table', 'layout' ) );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function renderCartItemInfo( BooklyLib\UserBookingData $userData, $cart_key, $positions, $desktop )
    {
        $cart_items = $userData->cart->getItems();
        $cart_item = $cart_items[ $cart_key ];
        $template = $desktop ? 'item_discount' : 'item_discount_mobile';
        $discounts = Lib\Utils\Common::getServiceDiscounts( $cart_item->getServiceId(), $cart_item->getNumberOfPersons() );

        if ( ! empty( $discounts ) ) {
            self::renderTemplate( $template, compact( 'discounts', 'positions', 'cart_key' ) );
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareCartTotalPrice( $total, BooklyLib\UserBookingData $userData )
    {
        $discount = 0;
        foreach ( Lib\Utils\Common::getCartDiscounts( $userData ) as $_discount ) {
            $discount += ( $total - BooklyLib\Utils\Price::correction( $total, $_discount->getDiscount(), $_discount->getDeduction() ) );
        }

        return $total - $discount;
    }
}