<?php
namespace BooklyCustomerCabinet\Lib;

use Bookly\Lib;

class Updater extends Lib\Base\Updater
{
    public function update_6_5()
    {
        $new_pc_key = 'bookly_customer_cabinet_purchase_code';
        $old_pc_key = 'bookly_customer_cabinet_envato_purchase_code';
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

    public function update_1_1()
    {
        delete_option( 'bookly_customer_cabinet_enabled' );
    }
}