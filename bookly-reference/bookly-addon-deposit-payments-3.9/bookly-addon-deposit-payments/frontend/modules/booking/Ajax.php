<?php
namespace BooklyDepositPayments\Frontend\Modules\Booking;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Lib\Errors;

class Ajax extends BooklyLib\Base\Ajax
{
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Apply payment method
     */
    public static function applyPaymentMethod()
    {
        $userData = new BooklyLib\UserBookingData( self::parameter( 'form_id' ) );

        if ( $userData->load() ) {
            $userData->setDepositFull( self::parameter( 'deposit_full' ) )
                ->sessionSave();

            // Output JSON response.
            wp_send_json_success();
        }

        Errors::sendSessionError();
    }
}