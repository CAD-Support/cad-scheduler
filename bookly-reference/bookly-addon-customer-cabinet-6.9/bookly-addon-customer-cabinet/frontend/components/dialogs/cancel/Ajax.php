<?php
namespace BooklyCustomerCabinet\Frontend\Components\Dialogs\Cancel;

use Bookly\Lib as BooklyLib;

class Ajax extends BooklyLib\Base\Ajax
{
    /** @var BooklyLib\Entities\Customer */
    protected static $customer;

    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'customer', );
    }

    /**
     * Cancel appointment
     */
    public static function cancelAppointment()
    {
        $ca_id = self::parameter( 'ca_id' );

        $ca = BooklyLib\Entities\CustomerAppointment::find( $ca_id );
        if ( $ca->getCustomerId() == self::$customer->getId() ) {
            $appointment = new BooklyLib\Entities\Appointment();
            if ( $appointment->load( $ca->getAppointmentId() ) ) {
                $allow_cancel_time = strtotime( $appointment->getStartDate() ) - (int) BooklyLib\Proxy\Pro::getMinimumTimePriorCancel( $appointment->getServiceId() );
                if ( $appointment->getStartDate() === null || current_time( 'timestamp' ) <= $allow_cancel_time ) {
                    $ca->cancel( self::parameter( 'reason', '' ) );

                    wp_send_json_success();
                }
            }
        }

        wp_send_json_error();
    }

    /**
     * @inheritDoc
     */
    protected static function hasAccess( $action )
    {
        if ( parent::hasAccess( $action ) ) {
            self::$customer = BooklyLib\Entities\Customer::query()->where( 'wp_user_id', get_current_user_id() )->findOne();

            return self::$customer->isLoaded();
        }

        return false;
    }
}