<?php
namespace BooklyCustomerInformation\Lib;

use Bookly\Lib as BooklyLib;

class Updater extends BooklyLib\Base\Updater
{
    public function update_3_9()
    {
        $new_pc_key = 'bookly_customer_information_purchase_code';
        $old_pc_key = 'bookly_customer_information_envato_purchase_code';
        $current_pc = get_option( $old_pc_key, 'missing' );
        if ( $current_pc === 'missing' ) {
            add_option( $new_pc_key, '' );
        } else {
            if ( $current_pc ) {
                add_option( $new_pc_key, $current_pc );
            }
            delete_option( $old_pc_key );
        }
    }

    public function update_2_6()
    {
        delete_option( 'bookly_customer_information_enabled' );
    }
}