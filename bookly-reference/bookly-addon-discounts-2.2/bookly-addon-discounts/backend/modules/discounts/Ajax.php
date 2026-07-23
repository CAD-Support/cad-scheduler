<?php
namespace BooklyDiscounts\Backend\Modules\Discounts;

use Bookly\Lib as BooklyLib;
use BooklyDiscounts\Lib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Get list of discount.
     */
    public static function getDiscounts()
    {
        $discounts = Lib\Entities\Discount::query( 'd' )
            ->select( 'd.id, d.title, d.type, d.threshold, d.discount, d.deduction, d.date_start, d.date_end, d.enabled, COUNT(sd.service_id) AS services, sd.service_id' )
            ->leftJoin( 'ServiceDiscount', 'sd', 'sd.discount_id = d.id', 'BooklyDiscounts\Lib\Entities' )
            ->groupBy( 'd.id' )
            ->fetchArray();

        $types = Lib\Entities\Discount::getTypes();
        foreach ( $discounts as &$discount ) {
            $discount['condition'] = sprintf(
                __( 'If %s equals or exceeds %s', 'bookly' ),
                $types[ $discount['type'] ]['title'],
                $discount['threshold']
            );
            $discount['date_start_formatted'] = is_null( $discount['date_start'] ) ? '' : BooklyLib\Utils\DateTime::formatDate( $discount['date_start'] );
            $discount['date_end_formatted'] = is_null( $discount['date_end'] ) ? '' : BooklyLib\Utils\DateTime::formatDate( $discount['date_end'] );
        }

        wp_send_json( array(
            'draw' => (int) self::parameter( 'draw' ),
            'recordsTotal' => count( $discounts ),
            'recordsFiltered' => count( $discounts ),
            'data' => $discounts,
        ) );
    }

    /**
     * Remove discount(s).
     */
    public static function deleteDiscounts()
    {
        $discounts = array_map( 'intval', self::parameter( 'discounts', array() ) );
        Lib\Entities\Discount::query()->delete()->whereIn( 'id', $discounts )->execute();
        wp_send_json_success();
    }
}