<?php
namespace BooklyEvents\Lib\Payment\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\CartItem;
use Bookly\Lib\Payment\Proxy;
use Bookly\Lib\DataHolders;
use BooklyEvents\Lib\DataHolders\Details;
use Bookly\Lib\UserBookingData;
use BooklyEvents\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function create( $item_key, DataHolders\Booking\Order $order, CartItem $cart_item, UserBookingData $userData )
    {
        if ( $cart_item->getType() === CartItem::TYPE_EVENT ) {
            for ( $i = 0; $i < $cart_item->getNumberOfPersons(); $i++ ) {
                $item = new Lib\DataHolders\Booking\EventAttendee();
                $item->setTicketTypeId( $cart_item->getTicketTypeId() );

                $order->addItem( $item_key++, $item );
            }
        }

        return $item_key;
    }

    /**
     * @inerhitDoc
     */
    public static function paymentCreateDetailsFromItem( $details, DataHolders\Booking\Item $item )
    {
        if ( $item->getType() === DataHolders\Booking\Item::TYPE_EVENT_ATTENDEE ) {
            $details = new Details\EventAttendee();
        }

        return $details;
    }

    /**
     * @param string $default
     * @param CartItem $cart_item
     * @return string
     */
    public static function getTranslatedTitle( $default, CartItem $cart_item )
    {
        if ( $cart_item->getType() === CartItem::TYPE_EVENT ) {
            $default = Lib\Entities\EventTicketType::find( $cart_item->getTicketTypeId() )->getTranslatedTitle();
        }

        return $default;
    }

    /**
     * @inerhitDoc
     */
    public static function rollbackPayment( BooklyLib\Entities\Payment $payment )
    {
        Lib\Payment\ProxyProviders\Local::redeemReservedAttendees( $payment );
    }
}