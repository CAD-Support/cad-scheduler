<?php
namespace BooklyDiscounts\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array();
    }

    /**
     * Create tables in database.
     */
    public function createTables()
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        $charset_collate = $wpdb->has_cap( 'collation' )
            ? $wpdb->get_charset_collate()
            : 'DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci';

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\Discount::getTableName() . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) DEFAULT "",
                `type` ENUM("nop","appointments") NOT NULL DEFAULT "nop",
                `threshold` INT UNSIGNED DEFAULT NULL,
                `discount` DECIMAL(5,2) NOT NULL DEFAULT 0,
                `deduction` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `date_start` DATE DEFAULT NULL,
                `date_end` DATE DEFAULT NULL,
                `enabled` TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\ServiceDiscount::getTableName() . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `service_id` INT UNSIGNED NOT NULL,
                `discount_id` INT UNSIGNED NOT NULL,
                CONSTRAINT
                    FOREIGN KEY (service_id)
                    REFERENCES ' . BooklyLib\Entities\Service::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT
                    FOREIGN KEY (discount_id)
                    REFERENCES ' . Entities\Discount::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ) ENGINE = INNODB
                ' . $charset_collate
        );
    }

}