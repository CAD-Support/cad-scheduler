<?php
namespace BooklyEvents\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Proxy;
use BooklyEvents\Lib\Entities\Event;

class Local extends Proxy\Events
{
    /**
     * @inheritDoc
     */
    public static function getCalendarData( $data )
    {
        return self::getEventCalendarData( $data['item'] );
    }

    /**
     * @inheritDoc
     */
    public static function getEventCalendarData( Event $event )
    {
        $location_id = $event->getLocationId();
        $location = $location_id
            ? BooklyLib\Proxy\Locations::findById( $location_id )
            : null;
        $start = date_create( BooklyLib\Utils\DateTime::convertTimeZone( $event->getStartDate(), BooklyLib\Config::getWPTimeZone(), 'UTC' ) );
        $end = date_create( BooklyLib\Utils\DateTime::convertTimeZone( $event->getEndDate(), BooklyLib\Config::getWPTimeZone(), 'UTC' ) );
        $description = urlencode( $event->getTranslatedInfo() );
        $title = urlencode( $event->getTranslatedTitle() );

        return array( $start, $end, $title, $location, $description );
    }
}