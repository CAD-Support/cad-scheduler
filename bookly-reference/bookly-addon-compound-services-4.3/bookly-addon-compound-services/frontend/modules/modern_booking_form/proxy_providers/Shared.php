<?php
namespace BooklyCompoundServices\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyCompoundServices\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function prepareFormOptions( array $bookly_options )
    {
        $query = BooklyLib\Entities\Service::query( 's' )
            ->select( 'sub.*' )
            ->leftJoin( 'SubService', 'sub', 'sub.service_id = s.id' )
            ->where( 's.type', BooklyLib\Entities\Service::TYPE_COMPOUND );
        foreach ( $query->fetchArray() as $service ) {
            $bookly_options['complex_services'][ $service['service_id'] ][] = $service['sub_service_id'];
        }

        return $bookly_options;
    }
}