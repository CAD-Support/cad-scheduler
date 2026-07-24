<?php
namespace BooklyCustomerGroups\Backend\Components\Dialogs\Service\Edit\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Components\Dialogs\Service\Edit\Proxy;
use BooklyCustomerGroups\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function updateService( array $alert, BooklyLib\Entities\Service $service, array $parameters )
    {
        $group_ids = isset( $parameters['group_ids'] ) ? $parameters['group_ids'] : array();

        Lib\Entities\CustomerGroupsServices::query()->delete()->where( 'service_id', $service->getId() )->whereNotIn( 'group_id', $group_ids )->execute();
        $rows = Lib\Entities\CustomerGroupsServices::query()
            ->select( 'group_id' )
            ->where( 'service_id', $service->getId() )
            ->fetchArray();
        $existing_group_ids = array_map( function ( $row ) {
            return $row['group_id'];
        }, $rows );

        foreach ( $group_ids as $group_id ) {
            if ( ! in_array( $group_id, $existing_group_ids ) ) {
                $group_service = new Lib\Entities\CustomerGroupsServices();
                $group_service
                    ->setServiceId( $service->getId() )
                    ->setGroupId( $group_id )
                    ->save();
            }
        }

        return $alert;
    }
}