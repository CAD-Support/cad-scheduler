<?php
namespace BooklyEvents\Frontend\Modules\EventsForm;

use Bookly\Lib as BooklyLib;
use BooklyEvents\Lib\Entities\Event;
use BooklyEvents\Lib\Utils\Ics\Feed;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    public static function getEventsList()
    {
        $query = Event::query( 'e' )
            ->select( 'e.*, ett.id AS ticket_type_id, ett.title AS ticket_type_title, ett.quantity, ett.price, COUNT(ea.id) AS attendees_count, ett.reserved_ps, ett.reserved' )
            ->leftJoin( 'EventTicketType', 'ett', 'e.id = ett.event_id' )
            ->leftJoin( 'EventAttendee', 'ea', 'ett.id = ea.ticket_type_id' )
            ->where( 'e.published', 1 )
            ->whereGte( 'e.start_date', date_create( 'now', wp_timezone() )->modify( 'midnight' )->format( 'Y-m-d H:i:s' ) )
            ->groupBy( 'COALESCE(ett.id, CONCAT("ticket", e.id))' )
            ->sortBy( 'e.start_date ASC, ett.position' )
            ->order( 'ASC' );

        if ( $events_list = self::parameter( 'events_list' ) ) {
            $query->whereIn( 'e.id', $events_list );
        }

        $events = array();
        foreach ( $query->fetchArray() as $event ) {
            if ( ! isset( $events[ $event['id'] ] ) ) {
                $events[ $event['id'] ] = array(
                    'id' => $event['id'],
                    'title' => BooklyLib\Utils\Common::getTranslatedString( 'event_' . $event['id'], $event['title'] ),
                    'info' => nl2br( BooklyLib\Utils\Common::getTranslatedString( 'event_' . $event['id'] . '_info', $event['info'] ) ),
                    'start_date' => $event['start_date'],
                    'end_date' => $event['end_date'],
                    'duration' => BooklyLib\Utils\DateTime::secondsToInterval( strtotime( $event['end_date'] ) - strtotime( $event['start_date'] ) ),
                    'image' => BooklyLib\Utils\Common::getAttachmentUrl( $event['attachment_id'] ) ?: null,
                    'min_price' => null,
                    'max_price' => null,
                    'tickets' => array(),
                    'tags' => $event['tags'] ? json_decode( $event['tags'] ) : array(),
                    'max_capacity' => (int) $event['max_capacity'],
                );
            }
            if ( $event['ticket_type_id'] ) {
                $events[ $event['id'] ]['tickets'][] = array(
                    'id' => $event['ticket_type_id'],
                    'title' => BooklyLib\Utils\Common::getTranslatedString( 'event_ticket_type_' . $event['ticket_type_id'], $event['ticket_type_title'] ),
                    'quantity' => ! $event['sales_end_at'] || date_create( $event['sales_end_at'], wp_timezone() ) > date_create( 'now', wp_timezone() ) ? $event['quantity'] : 0,
                    'price' => $event['price'],
                    'attendees_count' => $event['attendees_count'] + $event['reserved_ps'] + $event['reserved'],
                );
                $events[ $event['id'] ]['min_price'] = $events[ $event['id'] ]['min_price'] === null ? $event['price'] : min( $events[ $event['id'] ]['min_price'], $event['price'] );
                $events[ $event['id'] ]['max_price'] = $events[ $event['id'] ]['max_price'] === null ? $event['price'] : max( $events[ $event['id'] ]['max_price'], $event['price'] );
            }
        }

        wp_send_json_success( array_values( $events ) );
    }

    public static function addEventToCalendar()
    {
        $event = Event::find( self::parameter( 'event_id' ) );
        if ( $event ) {
            $calendar = self::parameter( 'calendar' );
            if ( $calendar === 'ics' ) {
                $ics = Feed::createFromEvent( $event );

                header( 'Content-Type: text/calendar' );
                header( 'Content-Type: application/octet-stream', false );
                header( 'Content-Disposition: attachment; filename="Bookly_event_' . $event->getId() . '.ics"' );
                header( 'Content-Transfer-Encoding: binary' );

                echo $ics->render();
            } else {
                list( $start, $end, $title, $location, $description ) = \BooklyEvents\Frontend\Modules\Booking\ProxyProviders\Local::getEventCalendarData( $event );
                list( $link, $format ) = \Bookly\Frontend\Modules\Booking\Ajax::getCalendarSettings( self::parameter( 'calendar' ) );
                $redirect_url = sprintf(
                    $link,
                    $start->format( $format ),
                    $end->format( $format ),
                    $title,
                    $location ? $location->getTranslatedName() : '',
                    $description
                );
                BooklyLib\Utils\Common::redirect( $redirect_url );
            }
        }

        exit();
    }
}