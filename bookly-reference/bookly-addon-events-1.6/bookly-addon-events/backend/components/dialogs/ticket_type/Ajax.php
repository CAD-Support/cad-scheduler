<?php
namespace BooklyEvents\Backend\Components\Dialogs\TicketType;

use Bookly\Lib as BooklyLib;
use BooklyEvents\Lib\Entities;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'supervisor' );
    }

    /**
     * Retrieve the list of ticket types for the event.
     */
    public static function getTicketTypeList()
    {
        $data = array(
            'list' => Entities\EventTicketType::query( 'ett' )
                ->select( 'ett.id, ett.title, ett.quantity, ett.price, ett.reserved, ett.reserved_ps, COUNT(ea.id) AS sold' )
                ->leftJoin( 'EventAttendee', 'ea', 'ea.ticket_type_id = ett.id' )
                ->where( 'ett.event_id', self::parameter( 'event_id' ) )
                ->sortBy( 'ett.position ASC, ett.id' )
                ->groupBy( 'ett.id' )
                ->fetchArray() ?: array(),
        );

        wp_send_json_success( $data );
    }

    /**
     * Save the list of ticket types for the event
     */
    public static function saveTicketTypeList()
    {
        $event_id = self::parameter( 'event_id' );
        $list = self::parameter( 'list' ) ?: array();

        if ( empty ( $list ) ) {
            Entities\EventTicketType::query()
                ->delete()
                ->where( 'event_id', $event_id )
                ->execute();
        } else {
            $tickets = array();
            $new_tickets = array();
            foreach ( $list as $position => $ticket ) {
                $ticket['position'] = $position;
                if ( isset( $ticket['id'] ) ) {
                    $tickets[ $ticket['id'] ] = $ticket;
                } else {
                    $new_tickets[] = $ticket;
                }
            }

            $ids = array_keys( $tickets );

            Entities\EventTicketType::query()
                ->delete()
                ->where( 'event_id', $event_id )
                ->whereNotIn( 'id', $ids )
                ->execute();

            /** @var Entities\EventTicketType[] $exist_tickets */
            $exist_tickets = Entities\EventTicketType::query()->whereIn( 'id', $ids )->find();
            foreach ( $exist_tickets as $ticket ) {
                $ticket
                    ->setQuantity( $tickets[ $ticket->getId() ]['quantity'] )
                    ->setTitle( $tickets[ $ticket->getId() ]['title'] )
                    ->setPrice( $tickets[ $ticket->getId() ]['price'] )
                    ->setReserved( $tickets[ $ticket->getId() ]['reserved'] )
                    ->setPosition( $tickets[ $ticket->getId() ]['position'] )
                    ->save();
            }

            foreach ( $new_tickets as $ticket ) {
                $event_ticket_type = new Entities\EventTicketType();
                $event_ticket_type
                    ->setEventId( $event_id )
                    ->setTitle( $ticket['title'] )
                    ->setQuantity( $ticket['quantity'] )
                    ->setReserved( $ticket['reserved'] )
                    ->setPrice( $ticket['price'] )
                    ->setPosition( $ticket['position'] )
                    ->save();
            }
        }
        wp_send_json_success();
    }
}