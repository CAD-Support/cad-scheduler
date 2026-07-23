<?php
namespace BooklyPro\Lib\Payment\ProxyProviders;

use Bookly\Frontend\Modules\Payment;
use Bookly\Lib\CartInfo;
use Bookly\Lib\CartItem;
use Bookly\Lib\Config;
use Bookly\Lib\DataHolders;
use Bookly\Lib\Entities;
use Bookly\Lib\Payment\Proxy;
use Bookly\Lib\UserBookingData;
use BooklyPro\Lib\Payment\PayPalGateway;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function getGatewayByName( $gateway, Payment\Request $request )
    {
        if ( $gateway === Entities\Payment::TYPE_PAYPAL && get_option( 'bookly_paypal_enabled' ) === PayPalGateway::TYPE_EXPRESS_CHECKOUT ) {
            return new PayPalGateway( $request );
        }
        return $gateway;
    }

    /**
     * @inheritDoc
     */
    public static function applyGateway( CartInfo $cart_info, $gateway )
    {
        if ( $gateway === Entities\Payment::TYPE_PAYPAL && Config::paypalEnabled() ) {
            $cart_info->setGateway( $gateway );
        }

        return $cart_info;
    }

    /**
     * @inheritDoc
     */
    public static function prepareOutdatedUnpaidPayments( $payments )
    {
        $timeout = (int) get_option( 'bookly_paypal_timeout' );
        if ( $timeout && get_option( 'bookly_paypal_enabled' ) ) {
            $payments = array_merge( $payments, Entities\Payment::query()
                ->where( 'type', Entities\Payment::TYPE_PAYPAL )
                ->where( 'status', Entities\Payment::STATUS_PENDING )
                ->whereLt( 'created_at', date_create( current_time( 'mysql' ) )->modify( sprintf( '- %s seconds', $timeout ) )->format( 'Y-m-d H:i:s' ) )
                ->fetchCol( 'id' )
            );
        }

        return $payments;
    }

    /**
     * @inheritDoc
     */
    public static function showPaymentSpecificPrices( $show )
    {
        if ( ! $show ) {
            if ( Config::paypalEnabled() && self::paymentSpecificPriceExists( Entities\Payment::TYPE_PAYPAL ) ) {
                return true;
            }
        }

        return $show;
    }

    /**
     * @inerhitDoc
     */
    public static function paymentSpecificPriceExists( $gateway )
    {
        if ( $gateway === Entities\Payment::TYPE_PAYPAL ) {
            return get_option( 'bookly_paypal_increase' ) != 0 || get_option( 'bookly_paypal_addition' ) != 0;
        }
        return $gateway;
    }

    /**
     * @param string $default
     * @param CartItem $cart_item
     * @return string
     */
    public static function getTranslatedTitle( $default, CartItem $cart_item )
    {
        if ( $cart_item->getType() === CartItem::TYPE_CHILD_PAYMENT ) {
            $default = __( 'Child payment', 'bookly' );
        }

        return $default;
    }
}
