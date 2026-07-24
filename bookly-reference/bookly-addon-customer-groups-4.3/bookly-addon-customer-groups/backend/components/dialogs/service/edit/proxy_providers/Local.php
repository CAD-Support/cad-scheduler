<?php
namespace BooklyCustomerGroups\Backend\Components\Dialogs\Service\Edit\ProxyProviders;

use Bookly\Backend\Components\Controls\Inputs;
use Bookly\Backend\Components\Dialogs\Service\Edit\Proxy;
use BooklyCustomerGroups\Lib;

class Local extends Proxy\CustomerGroups
{
    /**
     * @inheritDoc
     */
    public static function renderVisibilityOption( $service )
    {
        Inputs::renderRadio( __( 'Customer group based', 'bookly' ), 'group', $service['visibility'] === 'group', array(  'name' => 'visibility' ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderSubForm( $service )
    {
        $groups_collection = Lib\Entities\CustomerGroups::query()->fetchArray();

        $rows = Lib\Entities\CustomerGroupsServices::query()
            ->select( 'group_id' )
            ->where( 'service_id', $service['id'] )
            ->fetchArray();

        $group_ids = array_map( function ( $row ) { return $row['group_id']; }, $rows );

        self::renderTemplate( 'sub_form', compact( 'groups_collection', 'group_ids', 'service' ) );
    }
}