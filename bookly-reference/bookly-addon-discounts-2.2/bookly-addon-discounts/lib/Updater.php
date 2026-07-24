<?php
namespace BooklyDiscounts\Lib;

use Bookly\Lib;

class Updater extends Lib\Base\Updater
{
    public function update_2_1()
    {
        $new_pc_key = 'bookly_discounts_purchase_code';
        $old_pc_key = 'bookly_discounts_envato_purchase_code';
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

    public function update_1_4()
    {
        $this->alterTables( array(
            'bookly_discounts' => array(
                'ALTER TABLE `%s` CHANGE COLUMN `discount` `discount` DECIMAL(5,2) NOT NULL DEFAULT \'0.00\'',
            ),
        ) );
    }
}