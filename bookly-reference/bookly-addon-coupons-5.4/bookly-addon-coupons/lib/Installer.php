<?php
namespace BooklyCoupons\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_coupons_enabled'               => get_option( 'bookly_pmt_coupons', '0' ),
            'bookly_l10n_info_coupon_single_app'   => __( 'The total price for the booking is {total_price}.', 'bookly' ),
            'bookly_l10n_info_coupon_several_apps' => __( 'You selected to book {appointments_count} appointments with total price {total_price}.', 'bookly' ),
            'bookly_l10n_label_coupon'             => __( 'Coupon', 'bookly' ),
            'bookly_l10n_coupon_error_invalid'     => __( 'This coupon code is invalid or has been used', 'bookly' ),
            'bookly_l10n_coupon_error_expired'     => __( 'This coupon code has expired', 'bookly' ),
            'bookly_coupons_default_code_mask'     => 'COUPON-****',
        );

        delete_option( 'bookly_pmt_coupons' );
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
            'CREATE TABLE IF NOT EXISTS `' . Entities\Coupon::getTableName() . '` (
                `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `code`              VARCHAR(255) NOT NULL DEFAULT "",
                `discount`          DECIMAL(5,2) NOT NULL DEFAULT 0,
                `deduction`         DECIMAL(10,2) NOT NULL DEFAULT 0,
                `usage_limit`       INT UNSIGNED NOT NULL DEFAULT 1,
                `used`              INT UNSIGNED NOT NULL DEFAULT 0,
                `once_per_customer` TINYINT(1) NOT NULL DEFAULT 0,
                `date_limit_start`  DATE DEFAULT NULL,
                `date_limit_end`    DATE DEFAULT NULL,
                `min_appointments`  INT UNSIGNED NOT NULL DEFAULT 1,
                `max_appointments`  INT UNSIGNED DEFAULT NULL
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        $wpdb->query(
            'ALTER TABLE `' . BooklyLib\Entities\Payment::getTableName() . '`
             ADD CONSTRAINT
                FOREIGN KEY (coupon_id)
                REFERENCES ' . Entities\Coupon::getTableName() . '(id)
                ON DELETE SET NULL
                ON UPDATE CASCADE'
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\CouponService::getTableName() . '` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `coupon_id`   INT UNSIGNED NOT NULL,
                `service_id`  INT UNSIGNED NOT NULL,
                CONSTRAINT
                    FOREIGN KEY (coupon_id)
                    REFERENCES  ' . Entities\Coupon::getTableName() . '(id)
                    ON DELETE   CASCADE
                    ON UPDATE   CASCADE,
                CONSTRAINT
                    FOREIGN KEY (service_id)
                    REFERENCES  ' . BooklyLib\Entities\Service::getTableName() . '(id)
                    ON DELETE   CASCADE
                    ON UPDATE   CASCADE
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\CouponStaff::getTableName() . '` (
                `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `coupon_id` INT UNSIGNED NOT NULL,
                `staff_id`  INT UNSIGNED NOT NULL,
                CONSTRAINT
                    FOREIGN KEY (coupon_id)
                    REFERENCES  ' . Entities\Coupon::getTableName() . '(id)
                    ON DELETE   CASCADE
                    ON UPDATE   CASCADE,
                CONSTRAINT
                    FOREIGN KEY (staff_id)
                    REFERENCES  ' . BooklyLib\Entities\Staff::getTableName() . '(id)
                    ON DELETE   CASCADE
                    ON UPDATE   CASCADE
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\CouponCustomer::getTableName() . '` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `coupon_id`   INT UNSIGNED NOT NULL,
                `customer_id` INT UNSIGNED NOT NULL,
                CONSTRAINT
                    FOREIGN KEY (coupon_id)
                    REFERENCES  ' . Entities\Coupon::getTableName() . '(id)
                    ON DELETE   CASCADE
                    ON UPDATE   CASCADE,
                CONSTRAINT
                    FOREIGN KEY (customer_id)
                    REFERENCES  ' . BooklyLib\Entities\Customer::getTableName() . '(id)
                    ON DELETE   CASCADE
                    ON UPDATE   CASCADE
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