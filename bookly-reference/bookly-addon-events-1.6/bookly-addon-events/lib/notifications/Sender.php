<?php
namespace BooklyEvents\Lib\Notifications;

use Bookly\Backend\Components\Dialogs\Queue\NotificationList;
use Bookly\Lib\DataHolders\Booking\Order;
use Bookly\Lib\Entities\Staff;
use Bookly\Lib\Notifications\Base;
use BooklyEvents\Lib\Entities;

abstract class Sender extends Base\Sender
{
    /**
     * Send notification about new attendees.
     *
     * @param Order $order
     * @param NotificationList|null $queue
     */
    public static function send( Order $order, $queue = null )
    {
        $notifications = array(
            'client' => array(),
            'staff' => array(),
        );

        $payment = $order->getPayment();
        $payment_status = $payment ? $payment->getStatus() : 'no_payment';
        foreach ( static::getNotifications( 'new_attendees' ) as $key => $list ) {
            foreach ( $list as $notification ) {
                $settings = $notification->getSettingsObject();
                if ( $settings->allowedPaymentWithStatus( $payment_status ) ) {
                    $notifications[ $key ][] = $notification;
                }
            }
        }

        if ( empty( $notifications['client'] ) && empty( $notifications['staff'] ) ) {
            return;
        }

        $codes = new Assets\Codes( $order );

        $reply_to = null;
        $attachments = new Assets\Attachments( $codes );

        $customer = $order->getCustomer();
        if ( $customer ) {
            foreach ( $notifications['client'] as $notification ) {
                static::sendToClient( $customer, $notification, $codes, $attachments, $queue );
            }

            if ( get_option( 'bookly_email_reply_to_customers' ) ) {
                $reply_to = array( 'email' => $customer->getEmail(), 'name' => $customer->getFullName() );
            }
        }

        if ( $notifications['staff'] ) {
            /** @var Entities\EventStaff[] $event_staff_list */
            $event_staff_list = Entities\EventStaff::query( 'es' )
                ->leftJoin( 'Event', 'e', 'e.id = es.event_id', '\BooklyEvents\Lib\Entities' )
                ->leftJoin( 'EventTicketType', 'ett', 'ett.event_id = e.id', '\BooklyEvents\Lib\Entities' )
                ->leftJoin( 'EventAttendee', 'ea', 'ea.ticket_type_id = ett.id', '\BooklyEvents\Lib\Entities' )
                ->where( 'ea.order_id', $order->getOrderId() )
                ->whereRaw( 'GREATEST(es.is_staff,es.is_organizer) = %d', array( 1 ) )
                ->find();

            $employers = self::getEmployers( $event_staff_list );

            // Notify staff & admins.
            foreach ( $notifications['staff'] as $notification ) {
                foreach ( $employers as $data ) {
                    $staff = $data['staff'];
                    $codes
                        ->setStaff( $data['is_staff'] ? $staff : null )
                        ->setOrganizer( $data['is_organizer'] ? $staff : null );
                    static::sendToStaff( $staff, $notification, $codes, $attachments, $reply_to, $queue );
                }
                $codes->setStaff( null )->setOrganizer( null );
                static::sendToAdmins( $notification, $codes, $attachments, $reply_to, $queue );
                static::sendToCustom( $notification, $codes, $attachments, $reply_to, $queue );
            }
        }
    }

    /**
     * @param Entities\Event $event
     * @param NotificationList|null $queue
     */
    public static function sendNewEvent( Entities\Event $event, $queue = null )
    {
        $notifications = static::getNotifications( 'new_event' );

        if ( empty( $notifications['client'] ) && empty( $notifications['staff'] ) ) {
            return;
        }

        $codes = new Assets\EventCodes( $event );

        $employers = Staff::query( 's' )
            ->leftJoin( 'EventStaff', 'es', 'es.staff_id = s.id', '\BooklyEvents\Lib\Entities' )
            ->where( 'es.event_id', $event->getId() )
            ->whereRaw( 'GREATEST(es.is_staff,es.is_organizer) = %d', array( 1 ) )
            ->find();

        $reply_to = null;
        $attachments = array();

        // Notify staff & admins.
        foreach ( $notifications['staff'] as $notification ) {
            foreach ( $employers as $staff ) {
                static::sendToStaff( $staff, $notification, $codes, $attachments, $reply_to, $queue );
            }
            static::sendToAdmins( $notification, $codes, $attachments, $reply_to, $queue );
            static::sendToCustom( $notification, $codes, $attachments, $reply_to, $queue );
        }
    }

    /**
     * @param Entities\EventAttendee $attendee
     * @param NotificationList|null $queue
     * @return void
     */
    public static function sendAttendeeDeleted( Entities\EventAttendee $attendee, $queue = null )
    {
        $notifications = static::getNotifications( 'attendee_deleted' );
        if ( empty( $notifications['client'] ) && empty( $notifications['staff'] ) ) {
            return;
        }

        $codes = new Assets\AttendeeCodes( $attendee );

        $attachments = array();

        foreach ( $notifications['client'] as $notification ) {
            static::sendToClient( $codes->getCustomer(), $notification, $codes, $attachments );
        }

        if ( $notifications['staff'] ) {
            $reply_to = null;
            /** @var Entities\EventStaff[] $event_staff_list */
            $event_staff_list = Entities\EventStaff::query( )
                ->where( 'event_id', $codes->getEvent()->getId() )
                ->whereRaw( 'GREATEST(is_staff,is_organizer) = %d', array( 1 ) )
                ->find();

            $employers = self::getEmployers( $event_staff_list );

            foreach ( $notifications['staff'] as $notification ) {
                foreach ( $employers as $data ) {
                    static::sendToStaff( $data['staff'], $notification, $codes, $attachments, $reply_to, $queue );
                }
                static::sendToAdmins( $notification, $codes, $attachments, $reply_to, $queue );
                static::sendToCustom( $notification, $codes, $attachments, $reply_to, $queue );
            }
        }
    }

    /**
     * @param Entities\EventStaff[] $event_staff_list
     * @return array
     */
    private static function getEmployers( array $event_staff_list )
    {
        $employers = array();
        if ( $event_staff_list ) {
            $staff_ids = array();
            foreach ( $event_staff_list as $es ) {
                if ( ! isset( $employers[ $es->getStaffId() ] ) ) {
                    $staff_ids[] = $es->getStaffId();
                    $employers[ $es->getStaffId() ] = array(
                        'staff' => null,
                        'is_organizer' => false,
                        'is_staff' => false,
                    );
                }
                if ( $es->isIsStaff() ) {
                    $employers[ $es->getStaffId() ]['is_staff'] = true;
                }
                if ( $es->isIsOrganizer() ) {
                    $employers[ $es->getStaffId() ]['is_organizer'] = true;
                }
            }
            $staff_list = Staff::query()->whereIn( 'id', $staff_ids )
                ->indexBy( 'id' )
                ->find();
            foreach ( $employers as $staff_id => &$data ) {
                $data['staff'] = $staff_list[ $staff_id ];
            }
        }

        return $employers;
    }
}