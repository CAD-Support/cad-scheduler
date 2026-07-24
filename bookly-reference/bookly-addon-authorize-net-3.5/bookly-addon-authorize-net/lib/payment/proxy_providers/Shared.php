<?php
namespace BooklyAuthorizeNet\Lib\Payment\ProxyProviders;

use Bookly\Frontend\Modules\Payment;
use Bookly\Lib\Payment\Proxy;
use Bookly\Lib\Entities;
use Bookly\Lib\CartInfo;
use BooklyAuthorizeNet\Lib\Payment\AuthorizeNetGateway;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function getGatewayByName( $gateway, Payment\Request $request )
    {
        if ( $gateway === Entities\Payment::TYPE_AUTHORIZENET ) {
            return new AuthorizeNetGateway( $request );
        }

        return $gateway;
    }

    /**
     * @inheritDoc
     */
    public static function paymentSpecificPriceExists( $gateway )
    {
        if ( $gateway === Entities\Payment::TYPE_AUTHORIZENET ) {
            return self::showPaymentSpecificPrices( false );
        }

        return $gateway;
    }

    /**
     * @inheritDoc
     */
    public static function applyGateway( CartInfo $cart_info, $gateway )
    {
        if ( $gateway === Entities\Payment::TYPE_AUTHORIZENET ) {
            $cart_info->setGateway( $gateway );
        }

        return $cart_info;
    }

    /**
     * @inheritDoc
     */
    public static function prepareOutdatedUnpaidPayments( $payments )
    {
        $timeout = (int) get_option( 'bookly_authorize_net_timeout' );
        if ( $timeout ) {
            $payments = array_merge( $payments, Entities\Payment::query()
                ->where( 'type', Entities\Payment::TYPE_AUTHORIZENET )
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
        return $show ?: ( get_option( 'bookly_authorize_net_increase' ) != 0 || get_option( 'bookly_authorize_net_addition' ) != 0 );
    }
}