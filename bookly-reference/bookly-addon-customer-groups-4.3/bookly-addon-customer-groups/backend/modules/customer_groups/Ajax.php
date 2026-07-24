<?php
namespace BooklyCustomerGroups\Backend\Modules\CustomerGroups;

use Bookly\Lib as BooklyLib;
use BooklyCustomerGroups\Lib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Get list of groups.
     */
    public static function getGroups()
    {
        $columns = BooklyLib\Utils\Tables::filterColumns( self::parameter( 'columns' ), BooklyLib\Utils\Tables::CUSTOMER_GROUPS );
        $order   = self::parameter( 'order', array() );
        $filter  = self::parameter( 'filter' );

        $query = Lib\Entities\CustomerGroups::query( 'cg' )
            ->select( '
                COUNT(c.id) AS customers_count,
                cg.*
            ' )
            ->leftJoin( 'Customer', 'c', 'c.group_id = cg.id', '\Bookly\Lib\Entities' )
            ->groupBy( 'cg.id' );

        foreach ( $order as $sort_by ) {
            $query->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? BooklyLib\Query::ORDER_DESCENDING : BooklyLib\Query::ORDER_ASCENDING );
        }

        $data = array();

        foreach ( $query->fetchArray() as $row ) {
            $data[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'customers_count' => $row['customers_count'],
                'description' => $row['description'],
                'appointment_status' => $row['appointment_status'] ? BooklyLib\Entities\CustomerAppointment::statusToString( $row['appointment_status'] ) : __( 'Default', 'bookly' ),
                'status' => $row['appointment_status'],
                'skip_payment' => (int) $row['skip_payment'],
                'discount' => $row['discount'],
                'gateways' => $row['gateways'] !== null ? json_decode( $row['gateways'], true ) : null,
            );
        }

        wp_send_json( array(
            'draw' => ( int ) self::parameter( 'draw' ),
            'recordsTotal' => count( $data ),
            'recordsFiltered' => count( $data ),
            'data' => $data,
        ) );
    }

    /**
     * Delete customer groups.
     */
    public static function deleteGroups()
    {
        Lib\Entities\CustomerGroups::query()->delete()->whereIn( 'id', (array) self::parameter( 'group_ids' ) )->execute();
        $query = BooklyLib\Entities\Customer::query( 'c' )
            ->select( 'c.id' )
            ->where( 'c.group_id', null );

        $no_groups_count = $query->count();

        wp_send_json_success( compact( 'no_groups_count' ) );
    }
}