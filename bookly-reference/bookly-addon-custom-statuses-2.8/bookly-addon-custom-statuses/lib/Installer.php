<?php
namespace BooklyCustomStatuses\Lib;

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
            'CREATE TABLE IF NOT EXISTS `' . Entities\CustomStatus::getTableName() . '` (
                `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `slug`     VARCHAR(255) NOT NULL,
                `name`     VARCHAR(255) DEFAULT NULL,
                `busy`     TINYINT(1) NOT NULL DEFAULT 1,
                `color`    VARCHAR(255) NOT NULL DEFAULT "#dddddd",
                `position` INT NOT NULL DEFAULT 9999,
                UNIQUE KEY unique_slug_idx (slug(191))
             ) ENGINE = INNODB
             ' . $charset_collate
        );
    }

    /**
     * @inheritDoc
     */
    public function removeData()
    {
        $default_status = get_option( 'bookly_appointment_default_status', 'missing' );
        if ( ( $default_status !== 'missing' )
            && ! in_array( $default_status, array( 'approved', 'pending' ) )
        ) {
            update_option( 'bookly_appointment_default_status', 'approved' );
        }

        parent::removeData();
    }
}