<?php
namespace BooklyEvents\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyEvents\Lib;
use BooklyEvents\Lib\Entities\Event;
use BooklyEvents\Lib\Entities\EventAttendee;
use BooklyEvents\Lib\Entities\EventTicketType;
use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\Request;
use Bookly\Frontend\Modules\Payment\Request as PaymentRequest;
use BooklyPro\Lib as BooklyProLib;

use Bookly\Frontend\Modules\ModernBookingForm\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function prepareAppearance( array $bookly_options )
    {
        if ( $bookly_options['type'] === BooklyProLib\Entities\Form::TYPE_EVENTS_FORM ) {
            $bookly_options['event_card_width'] = 420;
            $bookly_options['event_header_height'] = 120;
            $bookly_options['events_any'] = true;
            $bookly_options['show_stepper'] = true;
            $bookly_options['events_list'] = null;
            $bookly_options['show_filters'] = true;
            $bookly_options['show_add_event_to_calendar'] = true;
            $bookly_options['l10n']['text_events'] = __( 'Please select an event', 'bookly' );
            $bookly_options['l10n']['text_tickets'] = __( 'Please select a ticket', 'bookly' );
            $bookly_options['l10n']['remaining_tickets_text'] = _x( 'left', 'tickets left', 'bookly' );
            $bookly_options['l10n']['event_booking_completed'] = __( 'Your ticket has been created.', 'bookly' );
            $bookly_options['l10n']['steps']['events'] = __( 'Events', 'bookly' );
            $bookly_options['l10n']['steps']['tickets'] = __( 'Tickets', 'bookly' );
            $bookly_options['l10n']['steps_descriptions']['events'] = __( 'Select event', 'bookly' );
            $bookly_options['l10n']['steps_descriptions']['tickets'] = __( 'Event details', 'bookly' );
            $bookly_options['l10n']['tickets_summary'] = __( 'Summary', 'bookly' );
            $bookly_options['l10n']['sold_out'] = __( 'Sold out', 'bookly' );
            $bookly_options['l10n']['add_event_to_calendar'] = __( 'Add to calendar', 'bookly' );
            $bookly_options['l10n']['filter_search_title'] = __( 'Search', 'bookly' );
            $bookly_options['l10n']['filter_date_title'] = __( 'Event date', 'bookly' );
            $bookly_options['l10n']['filter_tags_title'] = __( 'Filter events', 'bookly' );
            $bookly_options['l10n']['filter_date_today'] = __( 'Today', 'bookly' );
            $bookly_options['l10n']['filter_date_tomorrow'] = __( 'Tomorrow', 'bookly' );
            $bookly_options['l10n']['filter_date_next7'] = __( 'Next 7 days', 'bookly' );
            $bookly_options['l10n']['filter_date_next30'] = __( 'Next 30 days', 'bookly' );
            $bookly_options['l10n']['filter_tags_all_selected'] = __( 'All selected', 'bookly' );

            $bookly_options['details_fields_show'] = array_diff( $bookly_options['details_fields_show'], array( 'custom_fields' ) );
            $bookly_options['details_fields_width'] = array_diff( $bookly_options['details_fields_show'], array( 'custom_fields' ) );
            $bookly_options['details_fields_order'] = array_diff( $bookly_options['details_fields_show'], array( 'custom_fields' ) );
        }

        return $bookly_options;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearanceData( array $bookly_options )
    {
        $bookly_options['fields']['event_card_width'] = __( 'Width', 'bookly' );
        $bookly_options['fields']['event_header_height'] = __( 'Header height', 'bookly' );
        $bookly_options['fields']['events_list'] = __( 'Events list', 'bookly' );
        $bookly_options['fields']['event_any'] = __( 'Any', 'bookly' );
        $bookly_options['fields']['event_custom'] = __( 'Custom', 'bookly' );
        $bookly_options['fields']['show_remaining_tickets'] = __( 'Show remaining tickets', 'bookly' );
        $bookly_options['fields']['remaining_tickets_text'] = __( 'Remaining tickets text', 'bookly' );
        $bookly_options['fields']['tickets_summary'] = __( 'Summary', 'bookly' );
        $bookly_options['fields']['sold_out'] = __( 'Sold out', 'bookly' );
        $bookly_options['fields']['filter_search_title'] = __( 'Search', 'bookly' );
        $bookly_options['fields']['filter_date_title'] = __( 'Event date', 'bookly' );
        $bookly_options['fields']['filter_tags_title'] = __( 'Filter events', 'bookly' );
        $bookly_options['fields']['filter_date_today'] = __( 'Today', 'bookly' );
        $bookly_options['fields']['filter_date_tomorrow'] = __( 'Tomorrow', 'bookly' );
        $bookly_options['fields']['filter_date_next7'] = __( 'Next 7 days', 'bookly' );
        $bookly_options['fields']['filter_date_next30'] = __( 'Next 30 days', 'bookly' );
        $bookly_options['fields']['filter_tags_all_selected'] = __( 'All selected', 'bookly' );

        $events = array();
        $rows = Event::query( 'e' )->select( 'e.id, e.title' )->where( 'e.published', 1 )->fetchArray();
        foreach ( $rows as $row ) {
            $events[] = array( 'id' => $row['id'], 'title' => BooklyLib\Utils\Common::getTranslatedString( 'event_' . $row['id'], $row['title'] ) );
        }

        $bookly_options['events'] = $events;

        return $bookly_options;
    }

    /**
     * @inheritDoc
     */
    public static function prepareFormOptions( $bookly_options )
    {
        $bookly_options['eventsTagsData'] = BooklyLib\Proxy\Pro::getTagsData( BooklyProLib\Entities\Tag::TYPE_EVENT );

        return $bookly_options;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareBookingResults( $data, $order )
    {

        $event_attendees = EventAttendee::query( 'ea' )
            ->leftJoin( 'Payment', 'p', 'p.id = ea.payment_id', '\Bookly\Lib\Entities' )
            ->where( 'p.status', BooklyLib\Entities\Payment::STATUS_COMPLETED )
            ->where( 'ea.order_id', $order->getId() )
            ->fetchCol( 'code' );
        if ( $event_attendees ) {
            $data['events_codes'] = $event_attendees;
        } else {
            // Fetch event attendees without payment to show codes if all payments systems are disabled.
            $event_attendees = EventAttendee::query( 'ea' )
                ->leftJoin( 'Order', 'o', 'o.id = ea.order_id', '\Bookly\Lib\Entities' )
                ->where( 'ea.payment_id', null )
                ->where( 'ea.order_id', $order->getId() )
                ->fetchCol( 'code' );
            if ( $event_attendees ) {
                $data['events_codes'] = $event_attendees;
            }
        }

        $events_list = EventAttendee::query( 'ea' )
            ->leftJoin( 'EventTicketType', 'ett', 'ett.id = ea.ticket_type_id' )
            ->leftJoin( 'Event', 'e', 'e.id = ett.event_id' )
            ->where( 'ea.order_id', $order->getId() )
            ->groupBy( 'e.id' )
            ->fetchCol( 'e.id' );
        if ( count( $events_list ) > 0 ) {
            $data['events'] = true;
            if ( count( $events_list ) === 1 ) {
                $data['qr'] = self::getIcs( $order );
            }
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public static function validate( Request $request )
    {
        // Total persons requested per event across the whole cart — an event may appear
        // under several ticket types, and the event-level cap is shared by all of them.
        $requested_by_event = array();
        foreach ( $request->getUserData()->cart->getItems() as $item ) {
            if ( $item->getType() === BooklyLib\CartItem::TYPE_EVENT ) {
                $tt = EventTicketType::find( $item->getTicketTypeId() );
                if ( $tt ) {
                    $requested_by_event[ $tt->getEventId() ] = ( $requested_by_event[ $tt->getEventId() ] ?? 0 ) + $item->getNumberOfPersons();
                }
            }
        }
        $occupied_cache = array();

        foreach ( $request->getUserData()->cart->getItems() as $cart_key => $item ) {
            if ( $item->getType() === BooklyLib\CartItem::TYPE_EVENT ) {
                $ticket_type = EventTicketType::find( $item->getTicketTypeId() );
                $event = Event::find( $ticket_type->getEventId() );
                $sold = EventAttendee::query()
                    ->where( 'ticket_type_id', $item->getTicketTypeId() )
                    ->count();

                $sales_ended = $event->getSalesEndAt() && date_create( $event->getSalesEndAt(), wp_timezone() ) < date_create( 'now', wp_timezone() );
                $type_over = ( $sold + $item->getNumberOfPersons() ) > ( $ticket_type->getQuantity() - $ticket_type->getReservedPs() - $ticket_type->getReserved() );

                // Event-level cap (sum across all ticket types). 0 = unlimited. Reserved
                // (admin hold) + reserved_ps (pending payment) count as occupied (Yuri).
                $event_over = false;
                $cap = (int) $event->getMaxCapacity();
                if ( $cap > 0 ) {
                    $event_id = $event->getId();
                    if ( ! isset( $occupied_cache[ $event_id ] ) ) {
                        $occupied = 0;
                        foreach ( EventTicketType::query()->where( 'event_id', $event_id )->find() as $ett ) {
                            $occupied += (int) $ett->getReserved() + (int) $ett->getReservedPs()
                                + EventAttendee::query()->where( 'ticket_type_id', $ett->getId() )->count();
                        }
                        $occupied_cache[ $event_id ] = $occupied;
                    }
                    $event_over = ( $occupied_cache[ $event_id ] + ( $requested_by_event[ $event_id ] ?? 0 ) ) > $cap;
                }

                if ( $sales_ended || $type_over || $event_over ) {
                    $request->setStep( BooklyLib\Config::cartActive() ? 'cart' : 'tickets' );
                    $notices = $request->getNotices();
                    $notices['slots'] = 'slot_not_available';
                    $request->setNotices( $notices );
                    $request->setData( array(
                        'failed_key' => $cart_key,
                        'attendees_count' => $sold,
                    ) );
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function processNullGateway( PaymentRequest $request )
    {
        $order_id = $request->getGateway()->getOrder()->getOrderId();
        foreach ( $request->getUserData()->cart->getItems() as $cart_key => $item ) {
            if ( $item->getType() === BooklyLib\CartItem::TYPE_EVENT ) {
                $event = Event::find( EventTicketType::find( $item->getTicketTypeId() )->getEventId() );
                for ( $i = 0; $i < $item->getNumberOfPersons(); $i++ ) {
                    $attendee = new EventAttendee();
                    $attendee
                        ->setTicketTypeId( $item->getTicketTypeId() )
                        ->setCustomerId( $request->getUserData()->getCustomer()->getId() )
                        ->setOrderId( $order_id )
                        ->setCodeMask( $event->getTicketMask() )
                        ->save();
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function pendingPaymentCreated( PaymentRequest $request )
    {
        foreach ( $request->getUserData()->cart->getItems() as $cart_key => $item ) {
            if ( $item->getType() === BooklyLib\CartItem::TYPE_EVENT ) {
                $ticket_type = EventTicketType::find( $item->getTicketTypeId() );
                $ticket_type
                    ->setReservedPs( $ticket_type->getReservedPs() + $item->getNumberOfPersons() )
                    ->save();
            }
        }
    }

    /**
     * @param BooklyLib\Entities\Order $order
     * @return string
     */
    protected static function getIcs( BooklyLib\Entities\Order $order )
    {
        $feed = new Lib\Utils\Ics\Feed();

        return $feed::createFromEventOrder( $order )->render();
    }
}