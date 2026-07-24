<?php
namespace BooklyCompoundServices\Backend\Components\Dialogs\Appointment\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Appointment\Edit\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareL10n( $l10n )
    {
        $l10n['l10n']['part_of_compound_services'] = __( 'Part of compound service', 'bookly' );

        return $l10n;
    }
}