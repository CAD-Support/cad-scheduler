<?php
namespace BooklyEvents\Lib;

use Bookly\Lib;

class Updater extends Lib\Base\Updater
{
    public function update_1_6()
    {
        $this->alterTables( array(
            'bookly_event_attendees' => array( 'ALTER TABLE `%s` ADD COLUMN `checked_in_at` DATETIME DEFAULT NULL AFTER `order_id`' ),
            'bookly_events' => array( 'ALTER TABLE `%s` ADD COLUMN `max_capacity` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `sales_end_at`' ),
        ) );
    }

    public function update_1_4()
    {
        $this->alterTables( array(
            'bookly_event_ticket_types' => array( 'ALTER TABLE `%s` ADD COLUMN `position` INT NOT NULL DEFAULT 9999 AFTER `price`' )
        ) );
    }

    public function update_1_3()
    {
        $this->alterTables( array(
            'bookly_events' => array(
                'ALTER TABLE `%s` ADD COLUMN `online_meeting_staff_id` INT UNSIGNED DEFAULT NULL AFTER `tags`',
                'ALTER TABLE `%s` ADD COLUMN `online_meeting_data` TEXT DEFAULT NULL AFTER `tags`',
                'ALTER TABLE `%s` ADD COLUMN `online_meeting_id` VARCHAR(255) DEFAULT NULL AFTER `tags`',
                'ALTER TABLE `%s` ADD COLUMN `online_meeting_provider` ENUM("off","zoom","google_meet","jitsi","bbb") NOT NULL DEFAULT "off" AFTER `tags`',
                'ALTER TABLE `%s` ADD COLUMN `wc_cart_info_name` VARCHAR(255) DEFAULT NULL AFTER `tags`',
                'ALTER TABLE `%s` ADD COLUMN `wc_product_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `tags`',
                'ALTER TABLE `%s` ADD CONSTRAINT FOREIGN KEY (online_meeting_staff_id) REFERENCES ' . $this->getTableName( 'bookly_staff' ) . '(id) ON DELETE SET NULL ON UPDATE CASCADE',
            ),
        ) );
    }

    public function update_1_2()
    {
        $new_pc_key = 'bookly_events_purchase_code';
        $old_pc_key = 'bookly_events_envato_purchase_code';
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
        $this->alterTables( array(
            'bookly_events' => array(
                'ALTER TABLE `%s` ADD COLUMN `tags` VARCHAR(255) DEFAULT NULL AFTER `published`',
                'ALTER TABLE `%s` ADD COLUMN `sales_end_at` DATETIME DEFAULT NULL AFTER `end_date`',
            ),
        ) );
    }
}