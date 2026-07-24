<?php
namespace BooklyCustomerGroups\Backend\Modules\Customers\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Customers\Proxy;

class Local extends Proxy\CustomerGroups
{
    /**
     * @inheritDoc
     */
    public static function prepareCustomerSelect( $select )
    {
        return $select . ', cg.name as group_name';
    }

    /**
     * @inheritDoc
     */
    public static function prepareCustomerQuery( BooklyLib\Query $query )
    {
        return $query->leftJoin( 'CustomerGroups', 'cg', 'c.group_id = cg.id', 'BooklyCustomerGroups\Lib\Entities' );
    }

    /**
     * @inheritDoc
     */
    public static function prepareCustomerListData( $data, $row )
    {
        $data['group_name'] = $row['group_name'];

        return $data;
    }
}