<?php
namespace BooklyDiscounts\Lib\Utils;

use Bookly\Lib\UserBookingData;
use BooklyDiscounts\Lib\Entities;

abstract class Common
{
    /**
     * @param int $service_id
     * @param int $nop
     * @return Entities\Discount[]
     */
    public static function getServiceDiscounts( $service_id, $nop )
    {
        $query = Entities\Discount::query( 'd' )
            ->innerJoin( 'ServiceDiscount', 'sd', 'sd.discount_id = d.id', '\BooklyDiscounts\Lib\Entities' )
            ->where( 'd.enabled', 1 )
            ->where( 'd.type', Entities\Discount::TYPE_NOP )
            ->whereLte( 'd.threshold', $nop )
            ->where( 'sd.service_id', $service_id )
            ->whereRaw( 'd.date_start IS NULL OR d.date_start <= %s', array( current_time( 'mysql' ) ) )
            ->whereRaw( 'd.date_end IS NULL OR d.date_end >= %s', array( current_time( 'mysql' ) ) )
            ->sortBy( 'd.threshold' );

        return $query->find();
    }

    /**
     * @param UserBookingData $userData
     *
     * @return array
     */
    public static function getCartDiscounts( UserBookingData $userData )
    {
        return Entities\Discount::query( 'd' )
            ->where( 'd.type', Entities\Discount::TYPE_APPOINTMENTS )
            ->where( 'd.enabled', 1 )
            ->whereLte( 'd.threshold', count( $userData->cart->getItems() ) )
            ->whereRaw( 'd.date_start IS NULL OR d.date_start <= %s', array( current_time( 'mysql' ) ) )
            ->whereRaw( 'd.date_end IS NULL OR d.date_end >= %s', array( current_time( 'mysql' ) ) )
            ->sortBy( 'd.threshold' )
            ->find();
    }
}