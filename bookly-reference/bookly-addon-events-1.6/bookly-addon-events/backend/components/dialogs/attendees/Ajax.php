<?php
namespace BooklyEvents\Backend\Components\Dialogs\Attendees;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\CartInfo;
use Bookly\Lib\Entities\Customer;
use Bookly\Lib\Entities\Payment;
use Bookly\Lib\UserBookingData;
use Bookly\Lib\Utils\Common;
use BooklyEvents\Lib\DataHolders;
use BooklyEvents\Lib\Entities;
use BooklyEvents\Lib\Notifications\Sender;
use Bookly\Backend\Components\Dialogs\Queue\NotificationList;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'supervisor' );
    }

    public static function getAttendeesFormData()
    {
        $data = array(
            'customers' => array(),
            'customers_loaded' => true,
            'ticket_types' => array(),
        );
        $customers_count = Customer::query( 'c' )->count();
        if ( $customers_count < Customer::REMOTE_LIMIT ) {
            /** @var Customer $customer */
            foreach ( Customer::query()->sortBy( 'full_name' )->find() as $customer ) {
                $data['customers'][] = array(
                    'id' => (int) $customer->getId(),
                    'full_name' => Common::stripWpKses( $customer->getFullName() ),
                    'email' => Common::stripWpKses( $customer->getEmail() ),
                    'phone' => Common::stripWpKses( $customer->getPhone() ),
                    'group_id' => BooklyLib\Config::customerGroupsActive() ? $customer->getGroupId() : 0,
                    'timezone' => BooklyLib\Proxy\Pro::getLastCustomerTimezone( $customer->getId() ),
                );
            }
        } else {
            $data['customers_loaded'] = false;
        }

        $data['ticket_types'] = Entities\EventTicketType::query( 'ett' )
            ->select( 'ett.id, ett.title, ett.quantity, ett.price, ett.event_id, ett.reserved, ett.reserved_ps, COUNT(ea.id) AS sold' )
            ->leftJoin( 'EventAttendee', 'ea', 'ea.ticket_type_id = ett.id' )
            ->sortBy( 'ett.title' )
            ->groupBy( 'ett.id' )
            ->fetchArray() ?: array();

        wp_send_json_success( $data );
    }

    /**
     * Retrieve the list of attendees for the event.
     */
    public static function getAttendees()
    {
        $list = Entities\EventAttendee::query( 'ea' )
            ->select( 'ea.id, ea.ticket_type_id, ea.customer_id, ea.code, ea.order_id, ea.checked_in_at, c.full_name, c.email, c.phone, p.status = \'completed\' AS paid, p.id AS payment_id' )
            ->leftJoin( 'Payment', 'p', 'p.id = ea.payment_id', '\Bookly\Lib\Entities' )
            ->leftJoin( 'EventTicketType', 'ett', 'ett.id = ea.ticket_type_id' )
            ->leftJoin( 'Customer', 'c', 'c.id = ea.customer_id', '\Bookly\Lib\Entities' )
            ->where( 'ett.event_id', self::parameter( 'event_id' ) )
            ->fetchArray() ?: array();

        $event = Entities\Event::find( self::parameter( 'event_id' ) );

        wp_send_json_success( array( 'list' => $list, 'max_capacity' => $event ? (int) $event->getMaxCapacity() : 0 ) );
    }

    /**
     * Save the list of attendees types for the event
     */
    public static function createAttendeeTickets()
    {
        $event = Entities\Event::find( self::parameter( 'event_id' ) );
        $customer = Customer::find( self::parameter( 'customer_id' ) );
        $ticket_type = Entities\EventTicketType::find( self::parameter( 'ticket_type_id' ) );
        $quantity = self::parameter( 'quantity' );

        if ( $event && $ticket_type && $quantity > 0 ) {

            $exist_tickets = Entities\EventAttendee::query( )
                ->where( 'ticket_type_id', $ticket_type->getId() )
                ->count();

            $orderEntity = new BooklyLib\Entities\Order();
            $orderEntity
                ->setToken( Common::generateToken( get_class( $orderEntity ), 'token' ) )
                ->save();

            $payment = new BooklyLib\Entities\Payment();
            $payment
                ->setType( Payment::TYPE_LOCAL )
                ->setTotal( 0 )
                ->setPaid( 0 )
                ->setPaidType( Payment::PAY_IN_FULL )
                ->setOrderId( $orderEntity->getId() )
                ->save();

            $order = new BooklyLib\DataHolders\Booking\Order( $customer  );
            $order
                ->setOrderId( $payment->getOrderId())
                ->setPayment( $payment );
            $total = 0;

            $list = array();
            for ( $i = 0; $i < $quantity; $i++ ) {
                $eea = new Entities\EventAttendee();
                $eea
                    ->setCodeMask( $event->getTicketMask() )
                    ->setCustomerId( $customer->getId() )
                    ->setTicketTypeId( $ticket_type->getId() )
                    ->setPaymentId( $payment->getId() )
                    ->setOrderId( $orderEntity->getId() )
                    ->save();
                $dea = new DataHolders\Booking\EventAttendee();
                $dea
                    ->setTicketTypeId( $eea->getTicketTypeId() )
                    ->setAttendeeId( $eea->getId() );

                $order->addItem( $i, $dea );
                $total += $ticket_type->getPrice();

                $list[] = array(
                    'id' => $eea->getId(),
                    'ticket_type_id' => $ticket_type->getId(),
                    'customer_id' => $customer->getId(),
                    'code' => $eea->getCode(),
                    'order_id' => $orderEntity->getId(),
                    'full_name' => $customer->getFullName(),
                    'email' => $customer->getEmail(),
                    'phone' => $customer->getPhone(),
                    'payment_id' => $payment->getId(),
                    'paid' => '0',
                );
            }
            $payment
                ->setTotal( $total )
                ->setStatus( $total > 0 ? Payment::STATUS_PENDING : Payment::STATUS_COMPLETED )
                ->setDetailsFromOrder( $order, new CartInfo( new UserBookingData( null ), false ) );

            $payment->save();
            if ( $payment->getStatus() === Payment::STATUS_COMPLETED ) {
                foreach ( $list as &$item ) {
                    $item['paid'] = '1';
                }
            }
            if ( self::parameter( 'use_reserved' ) ) {
                $ticket_type
                    ->setReserved( max( 0, $ticket_type->getReserved() - $quantity ) )
                    ->save();
            }

            $data = array(
                'sold' => $exist_tickets + $quantity,
                'order_id' => $orderEntity->getId(),
                'list' => $list,
                'total' => $payment->getTotal(),
                'reserved' => $ticket_type->getReserved(),
            );

            if ( self::parameter( 'send_notifications' ) ) {
                $queue = new NotificationList();
                Sender::send( $order, $queue );
                $list = $queue->getList();
                if ( $list ) {
                    $db_queue = new BooklyLib\Entities\NotificationQueue();
                    $db_queue
                        ->setData( json_encode( array( 'all' => $list ) ) )
                        ->save();

                    $data['queue'] = array( 'token' => $db_queue->getToken(), 'all' => $queue->getInfo() );
                }
            }

            // Manual admin add is allowed past the event cap, but warn when it overflows
            // (occupied = attendees + reserved + reserved_ps across all types). 0 = unlimited.
            $cap = (int) $event->getMaxCapacity();
            if ( $cap > 0 ) {
                $occupied = 0;
                foreach ( Entities\EventTicketType::query()->where( 'event_id', $event->getId() )->find() as $ett ) {
                    $occupied += (int) $ett->getReserved() + (int) $ett->getReservedPs()
                        + Entities\EventAttendee::query()->where( 'ticket_type_id', $ett->getId() )->count();
                }
                if ( $occupied > $cap ) {
                    $data['capacity_warning'] = sprintf( __( 'Event capacity exceeded: %1$d of %2$d', 'bookly' ), $occupied, $cap );
                }
            }

            wp_send_json_success( $data );
        }

        wp_send_json_error( array( 'message' => __( 'Failed', 'bookly' ) ) );
    }

    public static function deleteAttendeeTickets()
    {
        $ids = self::parameter( 'ids' ) ?: array();
        $attendees = Entities\EventAttendee::query( 'ea' )
            ->whereIn( 'ea.id', $ids )
            ->find();
        $data = array();
        foreach ( $attendees as $attendee ) {
            $queue = new NotificationList();
            Sender::sendAttendeeDeleted( $attendee, $queue );
            $list = $queue->getList();
            if ( $list ) {
                $db_queue = new BooklyLib\Entities\NotificationQueue();
                $db_queue
                    ->setData( json_encode( array( 'all' => $list ) ) )
                    ->save();

                $data['queue'] = array( 'token' => $db_queue->getToken(), 'all' => $queue->getInfo() );
            }
            $attendee->delete();
        }

        wp_send_json_success( $data );
    }

    /**
     * Mark / unmark an attendee ticket as checked in.
     */
    public static function checkInAttendee()
    {
        $attendee = Entities\EventAttendee::find( self::parameter( 'id' ) );
        if ( ! $attendee ) {
            wp_send_json_error();
        }
        $checked_in_at = self::parameter( 'state' ) ? current_time( 'mysql' ) : null;
        $attendee->setCheckedInAt( $checked_in_at )->save();

        wp_send_json_success( array( 'id' => (int) $attendee->getId(), 'checked_in_at' => $checked_in_at ) );
    }
}