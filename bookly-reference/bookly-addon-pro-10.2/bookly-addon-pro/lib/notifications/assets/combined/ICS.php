<?php
namespace BooklyPro\Lib\Notifications\Assets\Combined;

use Bookly\Lib as BooklyLib;

class ICS extends BooklyLib\Utils\Ics\Base
{
    /**
     * Constructor.
     *
     * @param Codes $codes
     * @param string $recipient
     */
    public function __construct( Codes $codes, $recipient = 'client' )
    {
        $description_template = $this->getDescriptionTemplate( $recipient );
        $this->data =
            "BEGIN:VCALENDAR\n"
            . "VERSION:2.0\n"
            . "PRODID:-//Bookly\n"
            . "CALSCALE:GREGORIAN\n";
        foreach ( $codes->cart_info as $item ) {
            /** @var BooklyLib\DataHolders\Booking\Simple $simple */
            $simple = $item['item'];
            if ( $simple->getAppointment()->getStartDate() ) {
                $this->empty = false;
                $description_codes = BooklyLib\Utils\Codes::getICSCodes( $simple );
                $this->data .= sprintf(
                    "BEGIN:VEVENT\n"
                    . "ORGANIZER;%s\n"
                    . "DTSTAMP:%s\n"
                    . "DTSTART:%s\n"
                    . "DTEND:%s\n"
                    . "SUMMARY:%s\n"
                    . "DESCRIPTION:%s\n"
                    . "LOCATION:%s\n"
                    . "END:VEVENT\n",
                    $this->escape( sprintf( 'CN=%s:mailto:%s', $item['staff_name'], $item['staff_email'] ) ),
                    $this->formatDateTime( $simple->getAppointment()->getStartDate() ),
                    $this->formatDateTime( $simple->getAppointment()->getStartDate() ),
                    $this->formatDateTime( $simple->getAppointment()->getEndDate() ),
                    $this->escape( $item['service_name'] ),
                    $this->escape( BooklyLib\Utils\Codes::replace( $description_template, $description_codes, false ) ),
                    $this->escape( sprintf( "%s", $item['location'] ) )
                );
            }
        }
        $this->data .= 'END:VCALENDAR';
    }
}