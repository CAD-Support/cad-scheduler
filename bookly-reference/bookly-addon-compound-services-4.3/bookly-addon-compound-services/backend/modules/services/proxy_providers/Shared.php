<?php
namespace BooklyCompoundServices\Backend\Modules\Services\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Services\Proxy;
use Bookly\Lib\Entities\Service;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareServiceIcons( $icons )
    {
        $icons[ Service::TYPE_COMPOUND ] = 'fas fa-ellipsis-h';

        return $icons;
    }

    /**
     * @inheritDoc
     */
    public static function prepareServiceTypes( $types )
    {
        $types[ Service::TYPE_COMPOUND ] = __( 'Compound', 'bookly' );

        return $types;
    }

    /**
     * @inerhitDoc
     */
    public static function serviceDeleted( $service )
    {
        if ( $service->getType() === Service::TYPE_COMPOUND ) {
            BooklyLib\Entities\CustomerAppointment::query()
                ->update()
                ->set( 'compound_service_id', null )
                ->set( 'compound_token', null )
                ->where( 'compound_service_id', $service->getId() )
                ->execute();
        }
    }
}