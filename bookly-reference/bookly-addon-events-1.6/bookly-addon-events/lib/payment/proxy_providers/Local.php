<?php
namespace BooklyEvents\Lib\Payment\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities;
use Bookly\Lib\Payment\Proxy;
use BooklyEvents\Lib\Entities\EventTicketType;
use BooklyEvents\Lib\Entities\EventAttendee;
use BooklyPro\Lib\Notifications;
use BooklyEvents\Lib\DataHolders;

class Local extends Proxy\Events
{
    /**
     * @inerhitDoc
     */
    public static function completeEventAttendee( Entities\Payment $payment )
    {
        $details = $payment->getDetailsData();
        $items = array();
        $events_attendees = array();
        foreach ( $details->getItems() as $item ) {
            if ( $item['type'] === 'event_attendee' ) {
                $attendee_details = new DataHolders\Details\EventAttendee( $item );
                $attendee_id = $attendee_details->getValue('id' );
                $attendee = $attendee_id
                    ? EventAttendee::find( $attendee_id )
                    : null;
                if ( ! $attendee ) {
                    $attendee = $attendee_details->createAttendee( $payment );
                }
                if ( $attendee ) {
                    $items[] = $attendee_details->getData();
                    $events_attendees[] = $attendee;
                }
            } else {
                $items[] = $item;
            }
        }
        if ( $events_attendees ) {
            $details->setData( compact( 'items' ) );
            $payment->save();
        }

        return $payment;
    }

    /**
     * @inheritDoc
     */
    public static function redeemReservedAttendees( BooklyLib\Entities\Payment $payment )
    {
        // Handle reserved attendees.
        $reserved_ticket_types = array();
        foreach ( $payment->getDetailsData()->getItems() as $item ) {
            if ( $item['type'] === BooklyLib\Entities\Payment::ITEM_EVENT_ATTENDEE && ( ! isset( $item['code'] ) || ! $item['code'] ) ) {
                if ( ! isset( $reserved_ticket_types[ $item['type_id'] ] ) ) {
                    $reserved_ticket_types[ $item['type_id'] ] = 1;
                } else {
                    $reserved_ticket_types[ $item['type_id'] ]++;
                }
            }
        }
        foreach ( $reserved_ticket_types as $ticket_type_id => $reserved ) {
            $ticket_type = EventTicketType::find( $ticket_type_id );
            if ( $ticket_type ) {
                $ticket_type
                    ->setReservedPs( $ticket_type->getReservedPs() >= $reserved ? $ticket_type->getReservedPs() - $reserved : 0 )
                    ->save();
            }
        }
    }
}