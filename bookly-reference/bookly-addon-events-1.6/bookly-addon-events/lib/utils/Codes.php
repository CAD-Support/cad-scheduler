<?php
namespace BooklyEvents\Lib\Utils;

use Bookly\Lib as BooklyLib;
use BooklyEvents\Lib\Entities;

class Codes extends BooklyLib\Utils\Codes
{
    /**
     * Get codes for Event entity
     *
     * @param Entities\Event $event
     * @param string $format
     * @return array
     */
    public static function getEventCodes( Entities\Event $event, $format = 'text' )
    {
        return array(
            'event_title' => $event->getTranslatedTitle(),
            'event_info' => $event->getInfo(),
            'event_image_url' => $event->getAttachmentId() ? BooklyLib\Utils\Common::getAttachmentUrl( $event->getAttachmentId(), 'full' ) : '',
            'event_image' => $format === 'html' ? BooklyLib\Utils\Common::getAttachmentUrl( $event->getAttachmentId(), 'full' ) : BooklyLib\Utils\Common::getImageTag( BooklyLib\Utils\Common::getAttachmentUrl( $event->getAttachmentId(), 'full' ), $event->getTranslatedTitle() ),
            'event_start_date' => BooklyLib\Utils\DateTime::formatDate( $event->getStartDate() ),
            'event_end_date' => BooklyLib\Utils\DateTime::formatDate( $event->getEndDate() ),
            'event_start_time' => BooklyLib\Utils\DateTime::formatTime( $event->getStartDate() ),
            'event_end_time' => BooklyLib\Utils\DateTime::formatTime( $event->getEndDate() ),
        );
    }

    /**
     * Get codes for EventAttendee entity
     *
     * @param Entities\EventAttendee $attendee
     * @param string $format
     * @return array
     */
    public static function getAttendeeCodes( Entities\EventAttendee $attendee, $format = 'text' )
    {
        $customer = BooklyLib\Entities\Customer::find( $attendee->getCustomerId() );
        $ticket_type = Entities\EventTicketType::find( $attendee->getTicketTypeId() );

        return array(
            'attendee_order' => $attendee->getOrderId(),
            'attendee_code' => $attendee->getCode(),
            'attendee_email' => $customer->getEmail(),
            'attendee_phone' => $customer->getPhone(),
            'attendee_first_name' => $customer->getFirstName(),
            'attendee_last_name' => $customer->getLastName(),
            'attendee_full_name' => $customer->getFullName(),
            'attendee_ticket_type' => $ticket_type->getTranslatedTitle(),
        );
    }
}