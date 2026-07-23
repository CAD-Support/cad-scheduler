<?php
namespace BooklyEvents\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyEvents\Lib;
use BooklyEvents\Backend;
use BooklyEvents\Frontend;
use BooklyEvents\Lib\Entities;
use BooklyEvents\Backend\Modules\Events\Page;

class Local extends BooklyLib\Proxy\Events
{

    /**
     * @inheritDoc
     */
    public static function addBooklyMenuItem()
    {
        $events = __( 'Events', 'bookly' );

        add_submenu_page( 'bookly-menu', $events, $events, 'read',
            Page::pageSlug(), function() { Page::render(); } );
    }

    /**
     * @inheritDoc
     */
    public static function getList( array $staff_ids, $start_date, $end_date )
    {
        $query = Entities\Event::query( 'e' )
            ->select( 'es.staff_id, e.location_id, e.start_date, e.end_date' )
            ->leftJoin( 'EventStaff', 'es', 'es.event_id = e.id' )
            ->where( 'e.published', 1 )
            ->whereIn( 'es.staff_id', $staff_ids )
            ->whereGte( 'e.end_date', $start_date->format( 'Y-m-d' ) )
            ->whereLte( 'e.start_date', $end_date->format( 'Y-m-d' ) );

        return $query->fetchArray();
    }

    /**
     * @inheritDoc
     */
    public static function getIcsFromOrder( BooklyLib\Entities\Order $order )
    {
        return Lib\Utils\Ics\Feed::createFromEventOrder( $order );
    }

    /**
     * @inheritDoc
     */
    public static function sendNotifications( BooklyLib\DataHolders\Booking\Order $order )
    {
        Lib\Notifications\Sender::send( $order, null );
    }

    /**
     * @inerhitDoc
     */
    public static function getTicketType( $event_ticket_type_id )
    {
        return Entities\EventTicketType::find( $event_ticket_type_id );
    }

    /**
     * @inerhitDoc
     */
    public static function getEvent( $event_id ) {
        return Entities\Event::find( $event_id );
    }
}