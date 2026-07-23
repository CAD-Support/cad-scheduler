<?php
namespace BooklyEvents\Lib\Notifications\Assets;

use Bookly\Lib\DataHolders;
use Bookly\Lib\Notifications\Assets\Base;
use BooklyEvents\Lib\Entities;
use Bookly\Lib\Utils;
use Bookly\Lib as BooklyLib;

class Codes extends Base\Codes
{
    /**
     * Staff member used to filter the list of event attendees.
     * If set, only attendees related to events of this staff member will remain in the list.
     *
     * @var BooklyLib\Entities\Staff|null
     */
    protected $staff;

    /**
     * Event organizer used to filter the list of attendees.
     * If set, only attendees related to events organized by this staff member will remain in the list.
     *
     * @var BooklyLib\Entities\Staff|null
     */
    protected $organizer;

    /** @var DataHolders\Booking\Order */
    protected $order;

    /**
     * Constructor.
     *
     * @param DataHolders\Booking\Order $order
     */
    public function __construct( DataHolders\Booking\Order $order )
    {
        $this->order = $order;
    }

    /**
     * Set staff member.
     *
     * @param BooklyLib\Entities\Staff|null $staff
     * @return $this
     */
    public function setStaff( $staff )
    {
        $this->staff = $staff;

        return $this;
    }

    /**
     * Set event organizer.
     *
     * @param BooklyLib\Entities\Staff|null $organizer
     * @return $this
     */
    public function setOrganizer( $organizer )
    {
        $this->organizer = $organizer;

        return $this;
    }

    /**
     * @return DataHolders\Booking\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @inheritDoc
     */
    protected function getReplaceCodes( $format )
    {
        $replace_codes = parent::getReplaceCodes( $format );
        $attendee_list = $this->loadAttendeesList( $format );

        $filtered_attendee_list = $this->filterAttendees( $attendee_list );

        $payment = $this->order->getPayment();
        $customer = $this->order->getCustomer();

        $codes = array(
            'order_id' => $this->order->getOrderId(),
            'attendees' => $filtered_attendee_list,
            'attendees_count' => count( $filtered_attendee_list ),

            // Customer details
            'client_address' => $customer->getAddress(),
            'client_email' => $customer->getEmail(),
            'client_first_name' => $customer->getFirstName(),
            'client_last_name' => $customer->getLastName(),
            'client_name' => $customer->getFullName(),
            'client_phone' => $customer->getPhone(),
            'client_birthday' => $customer->getBirthday() ? Utils\DateTime::formatDate( $customer->getBirthday() ) : '',
            'client_note' => $customer->getNotes(),

            // Payment details
            'amount_paid' => $payment ? Utils\Price::format( $payment->getPaid() ) : '',
            'amount_due' => $payment ? Utils\Price::format( $payment->getTotal() - $payment->getPaid() ) : '',
            'total_price' => $payment ? Utils\Price::format( $payment->getTotal() ) : '',
            'total_tax' => $payment ? Utils\Price::format( $payment->getTax() ) : '',
            'invoice_number' => $payment ? $payment->getInvoiceNumber() : '',
            'payment_status' => $payment ? $payment->getStatus() : '',
            'payment_type' => $payment ? BooklyLib\Entities\Payment::typeToString( $payment->getType() ) : '',
        );

        return array_merge( $replace_codes, $codes );
    }

    /**
     * Load and prepare list of attendees with all related data.
     *
     * @param string $format
     * @return array
     */
    private function loadAttendeesList( $format )
    {
        static $attendee_list = null;

        if ( $attendee_list !== null ) {
            return $this->formatAttendeesCodes( $format, $attendee_list );
        }

        /** @var Entities\EventAttendee[] $list */
        $list = Entities\EventAttendee::query()
            ->where( 'order_id', $this->order->getOrderId() )
            ->find();

        if ( ! $list ) {
            return array();
        }

        $ticket_type_ids = $event_ids = $customer_ids = array();
        $attendee_tt = $attendee_e_tt = array();

        foreach ( $list as $attendee ) {
            $ticket_type_id = $attendee->getTicketTypeId();

            $ticket_type_ids[] = $ticket_type_id;
            $attendee_tt[ $ticket_type_id ][] = $attendee;
            $customer_ids[] = $attendee->getCustomerId();
        }

        /** @var Entities\EventTicketType[] $ticket_types */
        $ticket_types = Entities\EventTicketType::query()
            ->whereIn( 'id', array_unique( $ticket_type_ids ) )
            ->indexBy( 'id' )
            ->find();

        foreach ( $ticket_types as $ticket_type ) {
            $event_ids[] = $ticket_type->getEventId();
            foreach ( $attendee_tt[ $ticket_type->getId() ] as $attendee ) {
                $attendee_e_tt[ $ticket_type->getEventId() ][ $ticket_type->getId() ][] = $attendee;
            }
        }

        /** @var Entities\Event[] $events */
        $events = Entities\Event::query()
            ->whereIn( 'id', array_unique( $event_ids ) )
            ->sortBy( 'start_date' )
            ->find();

        /** @var BooklyLib\Entities\Customer[] $customers */
        $customers = BooklyLib\Entities\Customer::query()
            ->whereIn( 'id', array_unique( $customer_ids ) )
            ->indexBy( 'id' )
            ->find();

        /** @var Entities\EventStaff[] $event_employers */
        $event_employers = Entities\EventStaff::query()
            ->whereIn( 'event_id', array_unique( $event_ids ) )
            ->find();

        $event_organizers = $event_staff_members = array();

        foreach ( $event_employers as $ee ) {
            $event_id = $ee->getEventId();
            if ( ! isset( $event_organizers[ $event_id ] ) ) {
                $event_organizers[ $event_id ] = array();
                $event_staff_members[ $event_id ] = array();
            }
            if ( $ee->isIsOrganizer() ) {
                $event_organizers[ $event_id ][] = $ee->getStaffId();
            }
            if ( $ee->isIsStaff() ) {
                $event_staff_members[ $event_id ][] = $ee->getStaffId();
            }
        }

        $attendee_list = array();

        foreach ( $events as $event ) {
            foreach ( $attendee_e_tt[ $event->getId() ] as $ticket_type_id => $attendees ) {
                $ticket_type = $ticket_types[ $ticket_type_id ] ?: new Entities\EventTicketType();
                $event_id = $ticket_type->getEventId();

                foreach ( $attendees as $attendee ) {
                    $customer = $customers[ $attendee->getCustomerId() ] ?: new BooklyLib\Entities\Customer();
                    $location = $event->getLocationId()
                        ? BooklyLib\Proxy\Locations::findById( $event->getLocationId() )
                        : null;

                    $attendee_data = array(
                        '_' => array(
                            'staff_ids' => isset( $event_staff_members[ $event_id ] ) ? $event_staff_members[ $event_id ] : array(),
                            'organizer_ids' => isset( $event_organizers[ $event_id ] ) ? $event_organizers[ $event_id ] : array(),
                            'event' => $event,
                            'location' => $location,
                        ),
                        'event_title' => $event->getTranslatedTitle(),
                        'event_info' => $event->getTranslatedInfo(),
                        'event_start_date' => Utils\DateTime::formatDate( $event->getStartDate() ),
                        'event_end_date' => Utils\DateTime::formatDate( $event->getEndDate() ),
                        'event_start_time' => Utils\DateTime::formatTime( $event->getStartDate() ),
                        'event_end_time' => Utils\DateTime::formatTime( $event->getEndDate() ),
                        'event_image_url' => Utils\Common::getAttachmentUrl( $event->getAttachmentId(), 'full' ),
                        'ticket_name' => $ticket_type->getTitle(),
                        'ticket_price' => Utils\Price::format( $ticket_type->getPrice() ),
                        'ticket_quantity' => $ticket_type->getQuantity(),
                        'ticket_reserved' => $ticket_type->getReserved(),
                        'attendee_code' => $attendee->getCode(),
                        'attendee_email' => $customer->getEmail(),
                        'attendee_full_name' => $customer->getFullName(),
                        'attendee_first_name' => $customer->getFirstName(),
                        'attendee_last_name' => $customer->getLastName(),
                        'attendee_phone' => $customer->getPhone(),
                        'location_name' => $location ? $location->getTranslatedName() : '',
                        'location_info' => $location ? $location->getTranslatedInfo() : '',
                        'online_meeting_url' => BooklyLib\Proxy\Shared::buildOnlineMeetingUrl( '', $event, null ),
                        'online_meeting_password' => BooklyLib\Proxy\Shared::buildOnlineMeetingPassword( '', $event ),
                        'online_meeting_start_url' => BooklyLib\Proxy\Shared::buildOnlineMeetingStartUrl( '', $event ),
                        'online_meeting_join_url' => BooklyLib\Proxy\Shared::buildOnlineMeetingJoinUrl( '', $event, $customer ),
                    );

                    $attendee_data['event_image'] = $format === 'html' && $attendee_data['event_image_url']
                        ? Utils\Common::getImageTag( $attendee_data['event_image_url'], $event->getTitle() )
                        : $attendee_data['event_image_url'];

                    $attendee_list[] = $attendee_data;
                }
            }
        }

        return $attendee_list;
    }

    /**
     * Filter attendees by staff or organizer.
     *
     * @param array $attendee_list
     * @return array
     */
    private function filterAttendees( array $attendee_list )
    {
        if ( ! $this->staff && ! $this->organizer ) {
            return $attendee_list;
        }

        $filtered = array();

        $staff_id = $this->staff ? $this->staff->getId() : null;
        $organizer_id = $this->organizer ? $this->organizer->getId() : null;

        foreach ( $attendee_list as $attendee ) {
            if (
                ( $organizer_id && in_array( $organizer_id, $attendee['_']['organizer_ids'] ) )
                || ( $staff_id && in_array( $staff_id, $attendee['_']['staff_ids'] ) )
            ) {
                // Remove internal data.
                unset( $attendee['_'] );

                $filtered[] = $attendee;
            }
        }

        return $filtered;
    }

    /**
     * @param string $format
     * @param array $attendee_list
     * @return array
     */
    private function formatAttendeesCodes( $format, array $attendee_list )
    {
        foreach ( $attendee_list as &$data ) {
            foreach ( $data as $key => $value ) {
                if ( $key === 'event_image_url' ) {
                    if ( $format === 'html' && $value ) {
                        $data['event_image'] = Utils\Common::getImageTag( $value, $data['event_title'] );
                    } else {
                        $data['event_image'] = $value;
                    }
                }
            }

            // L10n
            $data['event_title'] = $data['_']['event']->getTranslatedTitle();
            $data['event_info'] = $data['_']['event']->getTranslatedInfo();
            if ( isset( $data['_']['location'] ) ) {
                $data['location_name'] = $data['_']['location']->getTranslatedName();
                $data['location_info'] = $data['_']['location']->getTranslatedInfo();
            }
        }

        return $attendee_list;
    }
}