<?php
namespace BooklyDepositPayments\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Appearance\Proxy\DepositPayments as DepositPaymentsProxy;

class Local extends DepositPaymentsProxy
{
    /**
     * Render editable selector for deposit/full payment
     */
    public static function renderAppearance()
    {
        if ( get_option( 'bookly_deposit_allow_full_payment' ) ) {
            self::renderTemplate( '_7_payment' );
        }
    }
}