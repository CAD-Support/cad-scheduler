<?php
namespace BooklyEvents\Lib\DataHolders\Details;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\DataHolders\Booking\Item;
use Bookly\Lib\DataHolders\Details;
use BooklyEvents\Lib\Entities;
use Bookly\Lib\Entities\Payment;

class EventAttendee extends Details\Base
{
    protected $type = Payment::ITEM_EVENT_ATTENDEE;

    protected $fields = array(
        'id',
        'code',
        'type_id',
        'title',
        'ticket_price',
        'tax',
        'service_name',
        'deposit_format',
        'appointment_date',
        'code',
    );

    /**
     * @param \BooklyEvents\Lib\DataHolders\Booking\EventAttendee $item
     * @return void
     */
    protected function setItem( Item $item )
    {
        $ticket_type = Entities\EventTicketType::find( $item->getTicketTypeId() );
        $this->price = $ticket_type->getPrice();
        $this->deposit = 0;
        $event = Entities\Event::find( $ticket_type->getEventId() );
        $code = null;
        if ( $item->getAttendeeId() ) {
            $code = Entities\EventAttendee::query( 'ea' )
                ->where( 'id', $item->getAttendeeId() )
                ->fetchVar( 'code' );
        }

        $this->setData( array(
            'id' => $item->getAttendeeId(),
            'type_id' => $ticket_type->getId(),
            'ticket_price' => $ticket_type->getPrice(),
            'title' => $ticket_type->getTitle(),
            'appointment_date' => $event->getStartDate(),
            'service_name' => $event->getTitle(),
            'code' => $code
        ) );
    }

    /**
     * @param BooklyLib\Entities\Payment $payment
     * @return Entities\EventAttendee|void
     */
    public function createAttendee( BooklyLib\Entities\Payment $payment )
    {
        $type = Entities\EventTicketType::find( $this->getValue( 'type_id' ) );
        if ( $type ) {
            $event = Entities\Event::find( $type->getEventId() );
            $attendee = new Entities\EventAttendee();
            $attendee
                ->setTicketTypeId( $type->getId() )
                ->setCustomerId( $payment->getDetailsData()->getValue( 'customer_id' ) )
                ->setPaymentId( $payment->getId() )
                ->setOrderId( $payment->getOrderId() )
                ->setCodeMask( $event->getTicketMask() )
                ->save();
            $this->setData( array(
                'id' => $attendee->getId(),
                'code' => $attendee->getCode(),
                'ticket_price' => $type->getPrice(),
            ) );

            $type
                ->setReservedPs( $type->getReservedPs() > 0 ? $type->getReservedPs() - 1 : 0 )
                ->save();

            return $attendee;
        }
    }
}