<?php
namespace BooklyCollaborativeServices\Lib;

class Updater extends \Bookly\Lib\Base\Updater
{
    public function update_3_9()
    {
        $new_pc_key = 'bookly_collaborative_services_purchase_code';
        $old_pc_key = 'bookly_collaborative_services_envato_purchase_code';
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

    public function update_2_1()
    {
        add_option( 'bookly_collaborative_hide_staff', '1' );
    }
}