<?php
namespace BooklyEvents\Lib\Utils\Ics;

use Bookly\Lib as BooklyLib;
use BooklyEvents\Lib\Entities\Event;
use BooklyEvents\Lib;

class Feed extends BooklyLib\Utils\Ics\Feed
{

    /**
     * @param BooklyLib\Entities\Order $order
     * @return Feed
     */
    public static function createFromEventOrder( BooklyLib\Entities\Order $order )
    {
        // Generate ICS feed.
        $ics = new self();
        /** @var Event[] $events */
        $events = Event::query( 'e' )
            ->leftJoin( 'EventTicketType', 'ett', 'ett.event_id = e.id' )
            ->leftJoin( 'EventAttendee', 'ea', 'ett.id = ea.ticket_type_id' )
            ->where( 'ea.order_id', $order->getId() )
            ->groupBy( 'e.id' )
            ->find();

        $description_template = self::getDescriptionTemplate();
        foreach ( $events as $event ) {
            $description_codes = Lib\Utils\Codes::getEventCodes( $event );
            $ics->addEvent(
                $event->getStartDate(),
                $event->getEndDate(),
                $event->getTranslatedTitle(),
                '',
                '',
                Lib\Utils\Codes::replace( $description_template, $description_codes, false ),
                $event->getLocationId()
            );
        }

        return $ics;
    }

    /**
     * @param Event $event
     * @return self
     */
    public static function createFromEvent( Event $event )
    {
        $ics = new self();
        $description_codes = Lib\Utils\Codes::getEventCodes( $event );
        $description_template = self::getDescriptionTemplate();
        $ics->addEvent(
            $event->getStartDate(),
            $event->getEndDate(),
            $event->getTranslatedTitle(),
            '',
            '',
            Lib\Utils\Codes::replace( $description_template, $description_codes, false ),
            $event->getLocationId()
        );

        return $ics;
    }

    public static function getDescriptionTemplate()
    {
        return "{event_title}\n{event_info}";
    }
}