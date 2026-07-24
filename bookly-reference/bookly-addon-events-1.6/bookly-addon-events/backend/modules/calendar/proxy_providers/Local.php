<?php
namespace BooklyEvents\Backend\Modules\Calendar\ProxyProviders;

use Bookly\Lib\Entities;
use Bookly\Backend\Modules\Calendar\Proxy;
use Bookly\Lib\Utils\Codes;
use Bookly\Lib\Utils\DateTime;
use BooklyEvents\Lib\Entities\Event;
use BooklyEvents\Lib\Entities\EventTicketType;
use BooklyPro\Lib\Notifications;
use BooklyEvents\Lib\DataHolders;

class Local extends Proxy\Events
{
    /**
     * @inerhitDoc
     */
    public static function buildEventsForCalendar( $events, $staff_members, $start_date, $end_date, $location_ids )
    {
        $staff_ids = array_map( function( $staff ) {
            return $staff->getId();
        }, $staff_members );

        $items = Event::query( 'e' )
            ->select( 'e.id, e.title, e.info, e.color, e.start_date, e.end_date, es.staff_id' )
            ->leftJoin( 'EventStaff', 'es', 'es.event_id = e.id' )
            ->where( 'e.published', 1 )
            ->whereIn( 'es.staff_id', $staff_ids )
            ->whereGte( 'e.start_date', $start_date->format( 'Y-m-d' ) )
            ->whereLte( 'e.end_date', $end_date->format( 'Y-m-d' ) )
            ->fetchArray();

        $event_ids = array_map( function( $event ) {
            return $event['id'];
        }, $items );

        $tickets = EventTicketType::query( 'ett' )
            ->select( 'COUNT(ea.id) AS attendees_count, ett.title, ett.event_id, ett.quantity, ett.reserved, ett.reserved_ps' )
            ->leftJoin( 'Event', 'e', 'e.id = ett.event_id' )
            ->leftJoin( 'EventAttendee', 'ea', 'ea.ticket_type_id = ett.id' )
            ->whereIn( 'e.id', $event_ids )
            ->groupBy( 'ett.id' )
            ->fetchArray();

        $ticket_tooltip_template = sprintf( '<div class="small">{title}<br><div class="text-muted">%s: {available}<br/>%s: {attendees_count}<br/>%s: {reserved}<br/>%s: {reserved_ps}<br/>%s: {quantity}</div></div><hr class="my-1"/>', __( 'Available', 'bookly' ), __( 'Sold', 'bookly' ), __( 'Reserved', 'bookly' ), __( 'On hold', 'bookly' ), __( 'Quantity', 'bookly' ) );
        $unique_events = array();
        foreach ( $items as $item ) {
            $description = '';
            $tooltip = '';
            foreach ( $tickets as $ticket ) {
                if ( $ticket['event_id'] == $item['id'] ) {
                    $ticket['available'] = max( 0, $ticket['quantity'] - $ticket['attendees_count'] - $ticket['reserved'] - $ticket['reserved_ps'] );
                    $description .= $ticket['title'] . ': ' . $ticket['available'] . '/' . $ticket['quantity'] . '<br/>';
                    $tooltip .= Codes::replace( $ticket_tooltip_template, $ticket, false );
                }
            }
            if ( $tooltip ) {
                // Remove the last <hr/> tag from the tooltip
                $tooltip = substr( $tooltip, 0, -18 );
            } else {
                $tooltip = __( 'Tickets are not yet available', 'bookly' );
            }
            $events[] = array(
                'start' => $item['start_date'],
                'end' => $item['end_date'],
                'color' => $item['color'],
                'resourceId' => $item['staff_id'],
                'extendedProps' => array(
                    'type' => 'event',
                    'id' => $item['id'],
                    'resources_only' => in_array( $item['id'], $unique_events ),
                    'tooltip' => $tooltip,
                    'desc' => $item['title'] . '<br/>' . $description,
                ),
            );
            if ( ! in_array( $item['id'], $unique_events ) ) {
                $unique_events[] = $item['id'];
            }
        }


        return $events;
    }
}