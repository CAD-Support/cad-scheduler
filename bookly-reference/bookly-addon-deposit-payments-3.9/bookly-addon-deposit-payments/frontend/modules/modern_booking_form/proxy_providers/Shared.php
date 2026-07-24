<?php
namespace BooklyDepositPayments\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function prepareFormOptions( array $bookly_options )
    {
        $bookly_options['deposit_mode'] = get_option( 'bookly_deposit_allow_full_payment', '0' );

        return $bookly_options;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearance( array $bookly_options )
    {
        $bookly_options['deposit_mode'] = get_option( 'bookly_deposit_allow_full_payment', '0' );

        $bookly_options['l10n']['deposit'] = _x( 'Deposit', 'portion of the payment', 'bookly' );
        $bookly_options['l10n']['deposit_label'] = __( 'Would you like to pay deposit or total price', 'bookly' );
        $bookly_options['l10n']['deposit_option'] = __( 'I will pay deposit', 'bookly' );
        $bookly_options['l10n']['full_price_option'] = __( 'I will pay total price', 'bookly' );

        return $bookly_options;
    }
}