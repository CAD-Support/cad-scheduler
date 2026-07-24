<?php
namespace BooklyEvents\Lib\Notifications\Assets;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Notification;
use Bookly\Lib\Notifications\Assets\Base;
use BooklyEvents\Lib\Utils\Ics\Feed;

class Attachments extends Base\Attachments
{
    /** @var Codes */
    protected $codes;

    /**
     * Constructor.
     *
     * @param Codes $codes
     */
    public function __construct( Codes $codes )
    {
        $this->codes = $codes;
    }

    /**
     * @inheritDoc
     */
    public function createFor( Notification $notification, $recipient = 'client' )
    {
        $files = array();
        if ( $notification->getAttachInvoice() ) {
            if ( ! isset ( $this->files['invoice'] ) ) {
                // Invoices.
                if ( $this->codes->getOrder()->hasPayment() ) {
                    $file = BooklyLib\Proxy\Invoices::getInvoice( $this->codes->getOrder()->getPayment() );
                    if ( $file ) {
                        $this->files['invoice'] = $file;
                    }
                }
            }
            if ( isset ( $this->files['invoice'] ) ) {
                $files[] = $this->files['invoice'];
            }
        }

        if ( $notification->getAttachIcs() ) {
            if ( ! isset( $this->files['ics'] ) ) {
                $feed = Feed::createFromEventOrder( BooklyLib\Entities\Order::find( $this->codes->getOrder()->getOrderId() ) );

                $file = $feed->create();
                if ( $file ) {
                    $this->files['ics'] = $file;
                }
            }

            if ( isset ( $this->files['ics'] ) ) {
                $files[] = $this->files['ics'];
            }
        }

        return $files;
    }
}