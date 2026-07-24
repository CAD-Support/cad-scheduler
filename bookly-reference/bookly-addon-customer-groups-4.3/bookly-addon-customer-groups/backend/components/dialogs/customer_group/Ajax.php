<?php
namespace BooklyCustomerGroups\Backend\Components\Dialogs\CustomerGroup;

use Bookly\Lib as BooklyLib;
use BooklyCustomerGroups\Lib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Save group.
     */
    public static function saveGroup()
    {
        $id = (int) self::parameter( 'id' );
        $name = self::parameter( 'name', '' );
        $description = self::parameter( 'description' );
        $status = self::parameter( 'status' );
        $discount = self::parameter( 'discount' );
        $skip_payment = (int) self::parameter( 'skip_payment' );
        $gateways = self::parameter( 'gateways' ) == 'default'
            ? null
            : self::parameter( 'gateways_list' );

        if ( self::parameter( 'is_general_settings' ) ) {
            update_option( 'bookly_customer_groups_general_settings', compact( 'status', 'skip_payment' , 'gateways', 'discount' ) );
        } else {
            if( $name == '' ) {
                wp_send_json_error( array( 'errors' => array( 'name_required' ) ) );
            }
            $group = new Lib\Entities\CustomerGroups();
            if ( $id ) {
                $group->load( $id );
            }
            $group
                ->setName( $name )
                ->setDescription( $description )
                ->setAppointmentStatus( $status )
                ->setDiscount( $discount )
                ->setSkipPayment( $skip_payment )
                ->setGateways( $gateways ? json_encode( $gateways ) : null )
                ->save();
        }

        wp_send_json_success();
    }
}