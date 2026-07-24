<?php
namespace BooklyCart\Lib;

use Bookly\Lib;

class Updater extends Lib\Base\Updater
{
    public function update_3_4()
    {
        $new_pc_key = 'bookly_cart_purchase_code';
        $old_pc_key = 'bookly_cart_envato_purchase_code';
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

    public function update_2_7()
    {
        if ( get_option( 'bookly_cart_enabled' ) && get_option( 'bookly_wc_enabled' ) && get_option( 'bookly_wc_product' ) && class_exists( 'WooCommerce', false ) ) {
            update_option( 'bookly_cart_enabled', '0' );
        }
    }

    public function update_2_3()
    {
        add_option( 'bookly_app_button_book_more_near_next', '0' );
    }
}