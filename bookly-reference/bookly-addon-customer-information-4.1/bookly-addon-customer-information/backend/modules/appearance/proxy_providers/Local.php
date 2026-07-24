<?php
namespace BooklyCustomerInformation\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;

class Local extends Proxy\CustomerInformation
{
    /**
     * @inheritDoc
     */
    public static function renderCustomerInformation()
    {
        self::renderTemplate( 'customer_information' );
    }
}