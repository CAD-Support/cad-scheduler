<?php
namespace BooklyDepositPayments\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function saveSettings( array $alert, $tab, array $params )
    {
        if ( $tab == 'payments' ) {
            $options = array( 'bookly_deposit_allow_full_payment' );
            foreach ( $options as $option_name ) {
                if ( array_key_exists( $option_name, $params ) ) {
                    update_option( $option_name, $params[ $option_name ] );
                }
            }
        }

        return $alert;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( $codes, $section )
    {
        switch ( $section ) {
            case 'woocommerce' :
            case 'ics_for_customer':
            case 'ics_for_staff':
                $codes['amount_due'] = array( 'description' => __( 'Amount due', 'bookly' ) );
                $codes['amount_to_pay'] = array( 'description' => __( 'Amount to pay', 'bookly' ) );
                $codes['deposit_value'] = array( 'description' => __( 'Total deposit amount to be paid', 'bookly' ) );
                break;
        }

        return $codes;
    }
}