<?php
namespace BooklyCustomStatuses\Lib;

use Bookly\Lib;

class Updater extends Lib\Base\Updater
{
    public function update_2_6()
    {
        $new_pc_key = 'bookly_custom_statuses_purchase_code';
        $old_pc_key = 'bookly_custom_statuses_envato_purchase_code';
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

    public function update_2_0()
    {
        $this->alterTables( array(
            'bookly_custom_statuses' => array(
                'ALTER TABLE `%s` ADD COLUMN `color` VARCHAR(255) NOT NULL DEFAULT "#dddddd" AFTER `busy`',
            ),
        ) );
    }

    public function update_1_3()
    {
        global $wpdb;

        $charset_collate = $wpdb->has_cap( 'collation' )
            ? $wpdb->get_charset_collate()
            : 'DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci';

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . $this->getTableName( 'bookly_custom_statuses' ) . '` (
                `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `slug`     VARCHAR(255) NOT NULL,
                `name`     VARCHAR(255) DEFAULT NULL,
                `busy`     TINYINT(1) NOT NULL DEFAULT 1,
                `position` INT NOT NULL DEFAULT 9999,
                UNIQUE KEY unique_slug_idx (slug(191))
             ) ENGINE = INNODB
             ' . $charset_collate
        );
    }

    public function update_1_1()
    {
        $this->upgradeCharsetCollate( array(
            'bookly_custom_statuses',
        ) );
    }
}