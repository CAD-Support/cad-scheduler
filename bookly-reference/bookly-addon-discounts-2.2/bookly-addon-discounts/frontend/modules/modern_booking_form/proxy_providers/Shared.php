<?php
namespace BooklyDiscounts\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyDiscounts\Lib\Entities\Discount;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function prepareFormOptions( array $bookly_options )
    {
        $discounts = Discount::query( 'd' )
            ->select( 'd.type, d.threshold, d.discount, d.deduction, GROUP_CONCAT(DISTINCT sd.service_id) AS service_ids' )
            ->leftJoin( 'ServiceDiscount', 'sd', 'sd.discount_id = d.id', 'BooklyDiscounts\Lib\Entities' )
            ->where( 'd.enabled', 1 )
            ->whereRaw( 'd.date_start IS NULL OR d.date_start <= %s', array( current_time( 'mysql' ) ) )
            ->whereRaw( 'd.date_end IS NULL OR d.date_end >= %s', array( current_time( 'mysql' ) ) )
            ->groupBy( 'd.id' )
            ->fetchArray();
        foreach ( $discounts as &$discount ) {
            $discount['service_ids'] = $discount['service_ids'] ? explode( ',', $discount['service_ids'] ) : array();
            $discount['discount'] = (int) $discount['discount'];
            $discount['deduction'] = (int) $discount['deduction'];
        }
        unset( $discount );

        $bookly_options['discounts'] = $discounts;

        return $bookly_options;
    }
}