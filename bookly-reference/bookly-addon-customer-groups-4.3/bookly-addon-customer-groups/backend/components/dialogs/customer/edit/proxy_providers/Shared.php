<?php
namespace BooklyCustomerGroups\Backend\Components\Dialogs\Customer\Edit\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Customer\Edit\Proxy;
use Bookly\Lib as BooklyLib;
use BooklyCustomerGroups\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareL10n( $localize )
    {
        $localize['groups'] = Lib\ProxyProviders\Local::getGroups();
        $localize['l10n']['group'] = __( 'Group', 'bookly' );
        $localize['l10n']['noGroup'] = __( 'No group', 'bookly' );

        return $localize;
    }
}