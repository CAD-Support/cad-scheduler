<?php
namespace BooklyEvents\Lib\Entities\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Proxy;
use BooklyEvents\Lib\Entities\Event;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function prepareOrderItems( $data, BooklyLib\Entities\Order $order )
    {
        /** @var Event $events */
        $events = Event::query( 'e' )
            ->leftJoin( 'EventTicketType', 'ett', 'ett.event_id = e.id' )
            ->leftJoin( 'EventAttendee', 'ea', 'ett.id = ea.ticket_type_id' )
            ->leftJoin( 'Order', 'o', 'o.id = ea.order_id', '\Bookly\Lib\Entities' )
            ->where( 'o.token', $order->getToken() )
            ->groupBy( 'e.id' )
            ->sortBy( 'e.id ASC, ett.id ASC, ea.code' )
            ->find();

        foreach ( $events as $event ) {
            $data[] = array(
                'title' => $event->getTranslatedTitle(),
                'item' => $event,
                'type' => 'event',
            );
        }

        return $data;
    }
}