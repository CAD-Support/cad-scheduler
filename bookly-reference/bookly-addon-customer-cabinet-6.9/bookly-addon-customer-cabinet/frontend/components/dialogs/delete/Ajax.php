<?php
namespace BooklyCustomerCabinet\Frontend\Components\Dialogs\Delete;

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
     * Check if future appointments exists to deny delete account
     */
    public static function checkFutureAppointments()
    {
        if ( ! self::_checkFutureAppointments( self::$customer->getId() ) ) {
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * Delete profile
     */
    public static function deleteProfile()
    {
        if ( ! self::_checkFutureAppointments( self::$customer->getId() ) ) {
            self::$customer->deleteWithWPUser( false );
            wp_delete_user( get_current_user_id() );
        }

        wp_send_json_success();
    }

    /**
     * @param $customer_id
     * @return bool
     */
    private static function _checkFutureAppointments( $customer_id )
    {
        return BooklyLib\Entities\CustomerAppointment::query( 'ca' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->where( 'ca.customer_id', $customer_id )
            ->whereRaw( 'a.start_date >= %s OR a.start_date IS NULL', array( current_time( 'mysql' ) ) )
            ->whereIn( 'ca.status', BooklyLib\Proxy\CustomStatuses::prepareBusyStatuses( array(
                BooklyLib\Entities\CustomerAppointment::STATUS_APPROVED,
                BooklyLib\Entities\CustomerAppointment::STATUS_PENDING
            ) ) )
            ->count() > 0;
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