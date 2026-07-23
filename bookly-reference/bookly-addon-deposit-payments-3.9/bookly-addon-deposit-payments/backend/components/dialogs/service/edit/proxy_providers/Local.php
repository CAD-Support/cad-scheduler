<?php
namespace BooklyDepositPayments\Backend\Components\Dialogs\Service\Edit\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Components\Dialogs\Service\Edit\Proxy;

class Local extends Proxy\DepositPayments
{
    /**
     * @inheritDoc
     */
    public static function renderDeposit( $service )
    {
        if ( BooklyLib\Config::compoundServicesActive() || BooklyLib\Config::collaborativeServicesActive() ) {
            self::renderTemplate( 'deposit', compact( 'service' ) );
        }
    }
}