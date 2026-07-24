<?php
namespace BooklyEvents\Lib\DataHolders\Booking;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\DataHolders\Booking\Item;
use BooklyEvents\Lib\Entities;

class EventAttendee extends Item
{
    protected $type = Item::TYPE_EVENT_ATTENDEE;

    /** @var int */
    protected $ticket_type_id;
    protected $event;
    protected $ticket_type;
    protected $staff;
    /** @var int */
    protected $attendee_id;

    /**
     * @return int
     */
    public function getTicketTypeId()
    {
        return $this->ticket_type_id;
    }

    /**
     * @param int $ticket_type_id
     */
    public function setTicketTypeId( $ticket_type_id )
    {
        $this->ticket_type_id = $ticket_type_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getAttendeeId()
    {
        return $this->attendee_id;
    }

    /**
     * @param int $attendee_id
     * @return EventAttendee
     */
    public function setAttendeeId( $attendee_id )
    {
        $this->attendee_id = $attendee_id;

        return $this;
    }

    /**
     * @return Entities\EventTicketType|false
     */
    protected function getTicketType()
    {
        if ( ! $this->ticket_type ) {
            $this->ticket_type = Entities\EventTicketType::find( $this->ticket_type_id );
        }

        return $this->ticket_type;
    }

    /**
     * @return Entities\Event|false
     */
    protected function getEvent()
    {
        if ( ! $this->event ) {
            $this->event = Entities\Event::find( $this->getTicketType()->getEventId() );
        }

        return $this->event;
    }

    public function getAppointment()
    {
    }

    public function getCA()
    {
    }

    public function getDeposit()
    {
        return 0;
    }

    public function getExtras()
    {
        return array();
    }

    public function getService()
    {
        return $this->event;
    }

    public function getServiceDuration()
    {
        return date_create( $this->getEvent()->getEndDate() ) - date_create( $this->getEvent()->getStartDate() );
    }

    public function getServicePrice()
    {
        return $this->getTicketType()->getPrice();
    }

    public function getStaff()
    {
        return $this->staff;
    }

    public function getTax()
    {
        return 0;
    }

    public function getServiceTax()
    {
        return 0;
    }

    public function getTotalEnd()
    {
    }

    public function getTotalPrice()
    {
        return $this->getTicketType()->getPrice();
    }

    public function getItems()
    {
        return array( $this );
    }

    public function setStatus( $status )
    {
    }

    public function getLocationId()
    {
        return $this->getEvent()->getLocationId();
    }
}