<?php
namespace BooklyCoupons\Backend\Modules\Calendar\ProxyProviders;

use Bookly\Backend\Modules\Calendar\Proxy;
use Bookly\Lib\Query;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareAppointmentCodesData( array $codes, $appointment_data, $participants )
    {
        $codes['coupon'] = $appointment_data['coupon_code'];

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCalendarQuery( Query $query )
    {
        return $query
            ->addSelect( 'cp.code AS coupon_code' )
            ->leftJoin( 'Coupon', 'cp', 'cp.id = p.coupon_id', 'BooklyCoupons\Lib\Entities' );
    }

}