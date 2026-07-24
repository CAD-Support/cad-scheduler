<?php
namespace BooklyDepositPayments\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;

class Local extends Proxy\DepositPayments
{
    /**
     * @inheritDoc
     */
    public static function renderPayments()
    {
        self::renderTemplate( 'deposit_options' );
    }
}