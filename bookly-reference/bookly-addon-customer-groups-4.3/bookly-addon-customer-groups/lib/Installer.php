<?php
namespace BooklyCustomerGroups\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_l10n_info_complete_step_group_skip_payment' => __( 'Thank you! Your booking is complete. An email with details of your booking has been sent to you.', 'bookly' ),
            'bookly_customer_groups_general_settings' => array(
                'status' => get_option( 'bookly_appointment_default_status', 'approved' ),
                'skip_payment' => '0',
                'gateways' => null,
                'discount' => '0',
            ),
        );
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
            'CREATE TABLE IF NOT EXISTS `' . Entities\CustomerGroups::getTableName() . '` (
                `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name`                  VARCHAR(255) NOT NULL,
                `description`           TEXT NOT NULL DEFAULT "",
                `appointment_status`    VARCHAR(255) NOT NULL DEFAULT "",
                `discount`              VARCHAR(100) NOT NULL DEFAULT "0",
                `skip_payment`          TINYINT(1) NOT NULL DEFAULT 0,
                `gateways`              VARCHAR(255) DEFAULT NULL
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        $wpdb->query(
            'ALTER TABLE `' . BooklyLib\Entities\Customer::getTableName() . '` 
             ADD CONSTRAINT 
                FOREIGN KEY (group_id)
                REFERENCES ' . Entities\CustomerGroups::getTableName() . '(id)
                ON DELETE SET NULL
                ON UPDATE CASCADE'
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\CustomerGroupsServices::getTableName() . '` (
                `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `group_id`          INT UNSIGNED NOT NULL,
                `service_id`        INT UNSIGNED NOT NULL,
                CONSTRAINT
                    FOREIGN KEY (group_id)
                    REFERENCES ' . Entities\CustomerGroups::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT
                    FOREIGN KEY (service_id)
                    REFERENCES ' . BooklyLib\Entities\Service::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE = INNODB
            ' . $charset_collate
        );
    }

    /**
     * @inheritDoc
     */
    public function removeData()
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        parent::removeData();

        // Remove user meta.
        $meta_names = array(
            $this->getPrefix() . 'table_settings',
        );
        $wpdb->query( $wpdb->prepare( sprintf( 'DELETE FROM `' . $wpdb->usermeta . '` WHERE meta_key IN (%s)',
            implode( ', ', array_fill( 0, count( $meta_names ), '%s' ) ) ), $meta_names ) );
    }
}