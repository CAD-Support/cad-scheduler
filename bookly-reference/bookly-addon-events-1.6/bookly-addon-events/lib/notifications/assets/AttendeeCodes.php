<?php
namespace BooklyEvents\Lib\Notifications\Assets;

use Bookly\Lib\Entities\Customer;
use BooklyEvents\Lib\Entities;
use Bookly\Lib\Utils;

class AttendeeCodes extends EventCodes
{
    /** @var Entities\EventAttendee */
    protected $attendee;
    /** @var Customer */
    protected $customer;

    /**
     * Constructor.
     *
     * @param Entities\EventAttendee $attendee
     */
    public function __construct( Entities\EventAttendee $attendee )
    {
        $this->attendee = $attendee;

        /** @var Entities\Event $event */
        $event = Entities\Event::query( 'e' )
            ->leftJoin( 'EventTicketType', 'ett', 'ett.event_id = e.id' )
            ->leftJoin( 'EventAttendee', 'ea', 'ea.ticket_type_id = ett.id' )
            ->where( 'ea.id', $this->attendee->getId() )
            ->findOne();

        parent::__construct( $event );
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        if ( ! $this->customer ) {
            $this->customer = Customer::find( $this->attendee->getCustomerId() );
        }

        return $this->customer;
    }

    /**
     * @inheritDoc
     */
    protected function getReplaceCodes( $format )
    {
        $replace_codes = parent::getReplaceCodes( $format );
        $customer = $this->getCustomer();

        /** @var Entities\EventTicketType $ticket_type */
        $ticket_type = Entities\EventTicketType::find( $this->attendee->getTicketTypeId() );

        $codes = array(
            'order_id' => $this->attendee->getOrderId(),
            // Customer details
            'client_address' => $customer->getAddress(),
            'client_email' => $customer->getEmail(),
            'client_first_name' => $customer->getFirstName(),
            'client_last_name' => $customer->getLastName(),
            'client_name' => $customer->getFullName(),
            'client_phone' => $customer->getPhone(),
            'client_birthday' => $customer->getBirthday() ? Utils\DateTime::formatDate( $customer->getBirthday() ) : '',
            'client_note' => $customer->getNotes(),

            // Attendee details
            'attendee_code' => $this->attendee->getCode(),
            'attendee_email' => $customer->getEmail(),
            'attendee_full_name' => $customer->getFullName(),
            'attendee_first_name' => $customer->getFirstName(),
            'attendee_last_name' => $customer->getLastName(),
            'attendee_phone' => $customer->getPhone(),

            // Ticket details
            'ticket_name' => $ticket_type->getTitle(),
            'ticket_price' => Utils\Price::format( $ticket_type->getPrice() ),
        );

        return array_merge( $replace_codes, $codes );
    }
}