<?php
namespace BooklyAuthorizeNet\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Lib\Entities\Payment;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function preparePaymentGatewaySettings( $payment_data )
    {
        $type = Payment::TYPE_AUTHORIZENET;
        $payment_data[ $type ] = self::renderTemplate( 'payment_settings', compact( 'type' ), false );

        return $payment_data;
    }

    /**
     * @inheritDoc
     */
    public static function saveSettings( array $alert, $tab, array $params )
    {
        if ( $tab === 'payments' ) {
            $options = array(
                'bookly_authorize_net_enabled',
                'bookly_authorize_net_api_login_id',
                'bookly_authorize_net_transaction_key',
                'bookly_authorize_net_sandbox',
                'bookly_authorize_net_increase',
                'bookly_authorize_net_addition',
                'bookly_authorize_net_send_tax',
                'bookly_authorize_net_timeout',
            );
            foreach ( $options as $option_name ) {
                if ( array_key_exists( $option_name, $params ) ) {
                    update_option( $option_name, trim( $params[ $option_name ] ) );
                }
            }
        }

        return $alert;
    }
}