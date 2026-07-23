<?php
namespace BooklyEvents\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Notification;
use BooklyEvents\Backend;
use BooklyEvents\Frontend;
use BooklyEvents\Lib\Entities;
use BooklyEvents\Lib\DataHolders\Booking;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * Append the Events booking form to the Appearance submenu in the fullscreen header sidebar.
     *
     * @param array $submenus
     * @return array
     */
    public static function buildHeaderSubmenus( $submenus )
    {
        $flag = \BooklyPro\Lib\Entities\Form::TYPE_EVENTS_FORM;
        if ( ! isset( $submenus['bookly-appearance'] ) ) {
            $submenus['bookly-appearance'] = array();
        }
        $submenus['bookly-appearance'][] = array(
            'label' => __( 'Events form', 'bookly' ),
            'url'   => admin_url( 'admin.php?page=bookly-appearance&' . $flag ),
            'flag'  => $flag,
            'badge' => '',
        );

        return $submenus;
    }

    /**
     * @inheritDoc
     */
    public static function prepareNotificationTitles( array $titles )
    {
        $titles[ Notification::TYPE_NEW_EVENT ]     = __( 'Notification about new event', 'bookly' );
        $titles[ Notification::TYPE_NEW_ATTENDEES ]     = __( 'Notification about new ticket', 'bookly' );
        $titles[ Notification::TYPE_ATTENDEE_DELETED ] = __( 'Notification about ticket deletion', 'bookly' );

        return $titles;
    }

    /**
     * @inheritDoc
     */
    public static function prepareNotificationTypes( array $types, $gateway )
    {
        $types[] = Notification::TYPE_NEW_EVENT;
        $types[] = Notification::TYPE_NEW_ATTENDEES;
        $types[] = Notification::TYPE_ATTENDEE_DELETED;

        return $types;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareCartInfo( BooklyLib\CartInfo $cart_info, BooklyLib\CartItem $item )
    {
        if ( $item->getType() === BooklyLib\CartItem::TYPE_EVENT ) {
            $ticket_type = Entities\EventTicketType::find( $item->getTicketTypeId() );
            if ( $ticket_type ) {
                $cart_info->setSubtotal( $cart_info->getSubtotal() + $ticket_type->getPrice() * $item->getNumberOfPersons() );
                $cart_info->setDeposit( $cart_info->getDeposit() + $ticket_type->getPrice() * $item->getNumberOfPersons() );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableColumns( $columns, $table )
    {
        if ( $table == BooklyLib\Utils\Tables::EVENTS ) {
            $columns = array(
                'id' => __( 'ID', 'bookly' ),
                'title' => __( 'Title', 'bookly' ),
                'start_date' => __( 'Start date', 'bookly' ),
                'end_date' => __( 'End date', 'bookly' ),
                'published' => __( 'Status', 'bookly' ),
                'tags' => __( 'Tags', 'bookly' ),
            );
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableDefaultSettings( $columns, $table )
    {
        if ( $table == BooklyLib\Utils\Tables::EVENTS ) {
            $columns = array_merge( $columns, array(
                'id' => false,
                'end_date' => false,
            ) );
        }

        return $columns;
    }

    /**
     * @inerhitDoc
     */
    public static function addItemsInOrder( $order, $order_id )
    {
        /** @var Entities\EventAttendee[] $att_list */
        $att_list = Entities\EventAttendee::query()->where( 'order_id', $order_id )->find();
        if ( $att_list ) {
            $payment_id = $att_list[0]->getPaymentId();
            $customer = BooklyLib\Entities\Customer::find( $att_list[0]->getCustomerId() );
            $att_order = BooklyLib\DataHolders\Booking\Order::create( $customer );
            if ( $payment_id ) {
                $att_order->setPayment( BooklyLib\Entities\Payment::find( $payment_id ) );
            }
            $att_order->setOrderId( $att_list[0]->getOrderId() );
            $item_key = 0;
            foreach ( $att_list as $attendee ) {
                $dea = new Booking\EventAttendee();
                $dea
                    ->setTicketTypeId( $attendee->getTicketTypeId() )
                    ->setAttendeeId( $attendee->getId() );

                $att_order->addItem( $item_key++, $dea );
            }

            if ( $order ) {
                foreach ( $att_order->getItems() as $position => $item ) {
                    $order->addItem( 'att_' . $position, $item );
                }
            } else {
                $order = $att_order;
            }
        }

        return $order;
    }
}
