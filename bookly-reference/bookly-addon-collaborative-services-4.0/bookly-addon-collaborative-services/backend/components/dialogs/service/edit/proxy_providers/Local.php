<?php
namespace BooklyCollaborativeServices\Backend\Components\Dialogs\Service\Edit\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Service\Edit\Proxy;

class Local extends Proxy\CollaborativeServices
{
    /**
     * @inheritDoc
     */
    public static function renderSubForm( array $service, array $simple_services )
    {
        self::renderTemplate( 'sub_form', compact( 'service', 'simple_services' ) );
    }
}