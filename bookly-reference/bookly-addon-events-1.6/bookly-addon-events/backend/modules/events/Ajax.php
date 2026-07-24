<?php
namespace BooklyEvents\Backend\Modules\Events;

use Bookly\Lib as BooklyLib;
use BooklyEvents\Lib\Entities;
use BooklyEvents\Lib\Notifications;
use Bookly\Backend\Components\Dialogs\Queue\NotificationList;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array(
            '_default' => 'supervisor',
        );
    }

    /**
     * Get list of events.
     */
    public static function getEvents()
    {
        $columns = BooklyLib\Utils\Tables::filterColumns( self::parameter( 'columns' ), BooklyLib\Utils\Tables::EVENTS );
        $order = self::parameter( 'order', array() );
        $filter = self::parameter( 'filter' );

        $query = Entities\Event::query( 'e' )
            ->select( '
                e.id,
                e.title,
                e.start_date,
                e.end_date,
                e.color,
                e.published,
                e.attachment_id,
                e.tags'
            );

        $total = $query->count();

        if ( ! empty( $filter['date'] ) && $filter['date'] !== 'any' ) {
            list ( $start, $end ) = explode( ' - ', $filter['date'], 2 );
            $end = date( 'Y-m-d', strtotime( $end ) + DAY_IN_SECONDS );
            $query->whereBetween( 'e.start_date', $start, $end );
        }

        if ( ! empty( $filter['published'] ) && is_array( $filter['published'] ) ) {
            $values = array_map( 'intval', $filter['published'] );
            $query->whereIn( 'e.published', $values );
        }

        if ( isset( $filter['search'] ) && $filter['search'] !== '' ) {
            $like = '%' . $filter['search'] . '%';
            $query->whereRaw( '(e.id LIKE %s OR e.title LIKE %s OR e.tags LIKE %s)', array( $like, $like, $like ) );
        }

        foreach ( $order as $sort_by ) {
            $query->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? BooklyLib\Query::ORDER_DESCENDING : BooklyLib\Query::ORDER_ASCENDING );
        }

        $filtered = $query->count();

        $query->limit( self::parameter( 'length' ) )->offset( self::parameter( 'start' ) );

        $data = array();

        foreach ( $query->fetchArray() as $row ) {
            $data[] = array(
                'id'             => $row['id'],
                'title'          => $row['title'],
                'image'          => BooklyLib\Utils\Common::getAttachmentUrl( $row['attachment_id'], 'thumbnail' ),
                'start_date'     => BooklyLib\Utils\DateTime::formatDate( $row['start_date'] ),
                'start_time'     => BooklyLib\Utils\DateTime::formatTime( $row['start_date'] ),
                'end_date'       => BooklyLib\Utils\DateTime::formatDate( $row['end_date'] ),
                'end_time'       => BooklyLib\Utils\DateTime::formatTime( $row['end_date'] ),
                'color'          => $row['color'],
                'published'      => (int) $row['published'],
                'tags'           => $row['tags'],
            );
        }

        BooklyLib\Utils\Tables::updateSettings( BooklyLib\Utils\Tables::EVENTS, $columns, $order, $filter );

        wp_send_json( array(
            'draw'            => (int) self::parameter( 'draw' ),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ) );
    }

    /**
     * Lightweight event summary for the preview hub (clicking a list row).
     *
     * Deliberately separate from getEvent (the heavy edit-form loader): the hub only
     * needs a glanceable overview, so this returns event basics plus inventory
     * aggregates per ticket type (sold + checked-in), letting the front-end build the
     * same capacity bar the attendees dialog uses without loading the full form payload.
     */
    public static function getEventSummary()
    {
        /** @var Entities\Event $event */
        $event = Entities\Event::find( self::parameter( 'id' ) );
        if ( ! $event ) {
            wp_send_json_error();
        }

        $ticket_types = Entities\EventTicketType::query( 'ett' )
            ->select( 'ett.id, ett.title, ett.quantity, ett.reserved, ett.reserved_ps, COUNT(ea.id) AS sold, COUNT(ea.checked_in_at) AS checked_in' )
            ->leftJoin( 'EventAttendee', 'ea', 'ea.ticket_type_id = ett.id' )
            ->where( 'ett.event_id', $event->getId() )
            ->groupBy( 'ett.id' )
            ->sortBy( 'ett.position ASC, ett.id' )
            ->fetchArray() ?: array();

        wp_send_json_success( array(
            'id'           => (int) $event->getId(),
            'title'        => $event->getTitle(),
            // 'medium' (not 'thumbnail') preserves the image's natural proportions —
            // WP hard-crops thumbnails to a square.
            'image'        => BooklyLib\Utils\Common::getAttachmentUrl( $event->getAttachmentId(), 'medium' ),
            'info'         => $event->getInfo(),
            'start_date'   => BooklyLib\Utils\DateTime::formatDate( $event->getStartDate() ),
            'start_time'   => BooklyLib\Utils\DateTime::formatTime( $event->getStartDate() ),
            'end_date'     => BooklyLib\Utils\DateTime::formatDate( $event->getEndDate() ),
            'end_time'     => BooklyLib\Utils\DateTime::formatTime( $event->getEndDate() ),
            'published'    => (int) $event->getPublished(),
            'max_capacity' => (int) $event->getMaxCapacity(),
            'ticket_types' => $ticket_types,
        ) );
    }

    /**
     * Delete events.
     */
    public static function deleteEvents()
    {
        $queue = new NotificationList();
        /** @var Entities\Event $event */
        foreach ( Entities\Event::query()->whereIn( 'id', (array) self::parameter( 'data' ) )->find() as $event ) {
            $attendees = Entities\EventAttendee::query( 'ea' )
                ->leftJoin( 'EventTicketType', 'ett', 'ett.id = ea.ticket_type_id' )
                ->where( 'ett.event_id', $event->getId() )
                ->sortBy( 'ett.event_id, ett.title' )
                ->find();

            foreach ( $attendees as $attendee ) {
                Notifications\Sender::sendAttendeeDeleted( $attendee, $queue );
            }

            $event->delete();
        }

        $response = array();
        $list = $queue->getList();
        if ( $list ) {
            $db_queue = new BooklyLib\Entities\NotificationQueue();
            $db_queue
                ->setData( json_encode( array( 'all' => $list ) ) )
                ->save();

            $response['queue'] = array( 'token' => $db_queue->getToken(), 'all' => $queue->getInfo() );
        }

        wp_send_json_success( $response );
    }
}
