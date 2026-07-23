<?php
namespace BooklyCompoundServices\Lib;

class Updater extends \Bookly\Lib\Base\Updater
{
    public function update_4_2()
    {
        $new_pc_key = 'bookly_compound_services_purchase_code';
        $old_pc_key = 'bookly_compound_services_envato_purchase_code';
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

    public function update_1_3()
    {
        delete_option( 'bookly_compound_services_enabled' );
    }
}