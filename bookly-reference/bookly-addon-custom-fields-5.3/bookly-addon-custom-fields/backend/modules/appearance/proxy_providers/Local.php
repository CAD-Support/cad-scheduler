<?php
namespace BooklyCustomFields\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;

class Local extends Proxy\CustomFields
{
    /**
     * @inheritDoc
     */
    public static function renderCustomFields()
    {
        self::renderTemplate( 'custom_fields' );
    }
}