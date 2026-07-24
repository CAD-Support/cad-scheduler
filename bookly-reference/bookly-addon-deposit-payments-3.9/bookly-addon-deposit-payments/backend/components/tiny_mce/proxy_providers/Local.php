<?php
namespace BooklyDepositPayments\Backend\Components\TinyMce\ProxyProviders;

use Bookly\Backend\Components\TinyMce\Proxy;

class Local extends Proxy\DepositPayments
{
    /**
     * @inheritDoc
     */
    public static function renderStaffCabinetSettings()
    {
        self::renderTemplate( 'staff_cabinet' );
    }
}