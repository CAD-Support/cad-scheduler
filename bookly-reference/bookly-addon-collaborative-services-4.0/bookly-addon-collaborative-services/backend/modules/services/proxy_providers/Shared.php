<?php
namespace BooklyCollaborativeServices\Backend\Modules\Services\ProxyProviders;

use Bookly\Backend\Modules\Services\Proxy;
use Bookly\Lib\Entities\Service;
use Bookly\Lib as BooklyLib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareServiceIcons( $icons )
    {
        $icons[ Service::TYPE_COLLABORATIVE ] = 'fas fa-align-left';

        return $icons;
    }

    /**
     * @inheritDoc
     */
    public static function prepareServiceTypes( $types )
    {
        $types[ Service::TYPE_COLLABORATIVE ] = __( 'Collaborative', 'bookly' );

        return $types;
    }

    /**
     * @inerhitDoc
     */
    public static function serviceDeleted( $service )
    {
        if ( $service->getType() === Service::TYPE_COLLABORATIVE ) {
            BooklyLib\Entities\CustomerAppointment::query()
                ->update()
                ->set( 'collaborative_service_id', null )
                ->set( 'collaborative_token', null )
                ->where( 'collaborative_service_id', $service->getId() )
                ->execute();
        }
    }
}