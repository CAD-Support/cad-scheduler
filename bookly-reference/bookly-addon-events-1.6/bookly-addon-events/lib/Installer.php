<?php
namespace BooklyEvents\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    protected $notifications = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_event_ticked_default_code_mask' => 'tc-***-***-***',
        );

        $ps = PHP_EOL . PHP_EOL . "{company_name}\n{company_phone}\n{company_website}";
        $client_prefix = __( 'Dear {client_name}', 'bookly' ) . ',' . PHP_EOL . PHP_EOL;

        // Notifications email & sms.
        $default_settings = json_decode( '{"status":"any","payment_statuses":["completed"],"option":2,"services":{"any":"any","ids":[]},"offset_hours":2,"perform":"before","at_hour":9,"before_at_hour":18,"offset_before_hours":-24,"offset_bidirectional_hours":0}', true );
        $this->notifications[] = array(
            'gateway' => 'email',
            'type' => 'new_event',
            'name' => __( 'Notification to staff member about new event', 'bookly' ),
            'subject' => __( 'Event {event_title} on {event_start_date} at {event_start_time}', 'bookly' ),
            'message' => __( "Hello,\nLet's organize {event_title} on {event_start_date} at {event_start_time}.", 'bookly' ),
            'active' => 1,
            'to_staff' => 1,
            'to_organizer' => 1,
            'settings' => $default_settings,
        );
        $this->notifications[] = array(
            'gateway' => 'sms',
            'type' => 'new_event',
            'name' => __( 'Notification to staff member about new event', 'bookly' ),
            'subject' => '',
            'message' => __( "Hello,\nLet's organize {event_title} on {event_start_date} at {event_start_time}.", 'bookly' ),
            'active' => 1,
            'to_staff' => 1,
            'to_organizer' => 1,
            'settings' => $default_settings,
        );

        $_settings = $default_settings;
        $_settings['payment_statuses'] = array( 'pending' );
        $this->notifications[] = array(
            'gateway' => 'email',
            'type' => 'new_attendees',
            'name' => __( 'Notification to customer about pending payment for reserved event ticket(s)', 'bookly' ),
            'subject' => __( 'Pending payment for your order #{order_id}', 'bookly' ),
            'message' => $client_prefix . __( "Thank you for booking with us! Your order #{order_id} has been successfully reserved. We're currently awaiting payment to confirm your tickets.", 'bookly' ) . $ps,
            'active' => 1,
            'to_customer' => 1,
            'settings' => $_settings,
        );
        $this->notifications[] = array(
            'gateway' => 'sms',
            'type' => 'new_attendees',
            'name' => __( 'Notification to customer about pending payment for reserved event ticket(s)', 'bookly' ),
            'subject' => '',
            'message' => $client_prefix . __( "Thank you for booking with us! Your order #{order_id} has been successfully reserved. We're currently awaiting payment to confirm your tickets.", 'bookly' ) . $ps,
            'active' => 1,
            'to_customer' => 1,
            'settings' => $_settings,
        );

        $this->notifications[] = array(
            'gateway' => 'email',
            'type' => 'new_attendees',
            'name' => __( 'Notification to customer about new event ticket(s)', 'bookly' ),
            'subject' => __( 'Order confirmation', 'bookly' ) . ' – #{order_id}',
            'message' => $client_prefix . __( "Thank you for your order!\nOrder: #{order_id}\nTickets: {attendees_count}\nTotal: {total_price}\n\nDetails:\n{#each attendees as attendee}\n {attendee.event_title} on {attendee.event_start_date} at {attendee.event_start_time} Ticket: {attendee.ticket_name} (Code: {attendee.attendee_code})\n{/each}", 'bookly' ) . $ps,
            'active' => 1,
            'to_customer' => 1,
            'settings' => $default_settings,
        );
        $this->notifications[] = array(
            'gateway' => 'sms',
            'type' => 'new_attendees',
            'name' => __( 'Notification to customer about new event ticket(s)', 'bookly' ),
            'subject' => '',
            'message' => $client_prefix . __( "Your tickets: {#each attendees as attendee}\n {attendee.event_title} on {attendee.event_start_date} at {attendee.event_start_time} Ticket: {attendee.ticket_name} (Code: {attendee.attendee_code})\n{/each}", 'bookly' ),
            'active' => 1,
            'to_customer' => 1,
            'settings' => $default_settings,
        );

        $this->notifications[] = array(
            'gateway' => 'email',
            'type' => 'new_attendees',
            'name' => __( 'Notification to organizer about new event ticket(s)', 'bookly' ),
            'subject' => __( 'Attendee', 'bookly' ) . ' – {client_name} {total_price} {payment_type}',
            'message' => __( "Hello,\nOrder: #{order_id}\nTickets: {attendees_count}\n{#each attendees as attendee}\n{attendee.event_title} - {attendee.attendee_full_name} {attendee.attendee_phone} (Code: {attendee.attendee_code})\n{/each}\n\nTotal: {total_price} {payment_type}", 'bookly' ),
            'active' => 1,
            'to_organizer' => 1,
            'settings' => $default_settings,
        );
        $this->notifications[] = array(
            'gateway' => 'sms',
            'type' => 'new_attendees',
            'name' => __( 'Notification to organizer about purchased new event tickets', 'bookly' ),
            'subject' => '',
            'message' => __( "Hello,\nOrder: #{order_id}\nTickets: {attendees_count}\n{#each attendees as attendee}\n{attendee.event_title} - {attendee.attendee_full_name} {attendee.attendee_phone} (Code: {attendee.attendee_code})\n{/each}\n\nTotal: {total_price} {payment_type}", 'bookly' ),
            'active' => 1,
            'to_organizer' => 1,
            'settings' => $default_settings,
        );

        $this->notifications[] = array(
            'gateway' => 'email',
            'type' => 'attendee_deleted',
            'name' => __( 'Notification to customer about deleted ticket(s)', 'bookly' ),
            'subject' => __( 'Your ticket {attendee_code} has been cancelled', 'bookly' ),
            'message' => $client_prefix . __( 'We regret to inform you that your ticket {attendee_code} for {event_title} on {event_start_date} at {event_start_time} has been cancelled.', 'bookly' ) . $ps,
            'active' => 1,
            'to_customer' => 1,
            'settings' => $default_settings,
        );
        $this->notifications[] = array(
            'gateway' => 'sms',
            'type' => 'attendee_deleted',
            'name' => __( 'Notification to customer about deleted ticket(s)', 'bookly' ),
            'subject' => '',
            'message' => $client_prefix . __( 'We regret to inform you that your ticket {attendee_code} for {event_title} on {event_start_date} at {event_start_time} has been cancelled.', 'bookly' ) . $ps,
            'active' => 1,
            'to_customer' => 1,
            'settings' => $default_settings,
        );

        $this->notifications[] = array(
            'gateway' => 'email',
            'type' => 'attendee_deleted',
            'name' => __( 'Notification to organizer about deleted ticket(s)', 'bookly' ),
            'subject' => __( 'Ticket {attendee_code} for {event_title} is deleted', 'bookly' ),
            'message' => __( "Hello.\nPlease note that attendee ticket {attendee_code} for {event_title} (Order #{order_id}) has been deleted.", 'bookly' ),
            'active' => 1,
            'to_organizer' => 1,
            'settings' => $default_settings,
        );
        $this->notifications[] = array(
            'gateway' => 'sms',
            'type' => 'attendee_deleted',
            'name' => __( 'Notification to organizer about deleted ticket(s)', 'bookly' ),
            'subject' => '',
            'message' => __( "Hello.\nPlease note that attendee ticket {attendee_code} for {event_title} (Order #{order_id}) has been deleted.", 'bookly' ),
            'active' => 1,
            'to_organizer' => 1,
            'settings' => $default_settings,
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
            'CREATE TABLE IF NOT EXISTS `' . Entities\Event::getTableName() . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `location_id` INT UNSIGNED NULL,
                `title` VARCHAR(64) NULL,
                `start_date` DATETIME DEFAULT NULL,
                `end_date` DATETIME DEFAULT NULL,
                `sales_end_at` DATETIME DEFAULT NULL,
                `max_capacity` INT UNSIGNED NOT NULL DEFAULT 0,
                `attachment_id` INT UNSIGNED DEFAULT NULL,
                `info` TEXT DEFAULT NULL,
                `ticket_mask` VARCHAR(32) NULL,
                `color` VARCHAR(32) NOT NULL DEFAULT "#FFFFFF",
                `published` TINYINT(1) NOT NULL DEFAULT 0,
                `tags` VARCHAR(255) DEFAULT NULL,
                `wc_product_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `wc_cart_info_name` VARCHAR(255) DEFAULT NULL,
                `online_meeting_provider` ENUM("off","zoom","google_meet","jitsi","bbb") NOT NULL DEFAULT "off",
                `online_meeting_id` VARCHAR(255) DEFAULT NULL,
                `online_meeting_data` TEXT DEFAULT NULL,
                `online_meeting_staff_id` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                CONSTRAINT
                    FOREIGN KEY (online_meeting_staff_id)
                    REFERENCES ' . BooklyLib\Entities\Staff::getTableName() . '(id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        if ( $this->existsTable( $wpdb->prefix . 'bookly_locations' ) ) {
            $wpdb->query(
                'ALTER TABLE `' . Entities\Event::getTableName() . '` 
                 ADD CONSTRAINT 
                    FOREIGN KEY (location_id)
                    REFERENCES ' . $wpdb->prefix . 'bookly_locations' . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE'
            );
        }

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\EventStaff::getTableName() . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `event_id` INT UNSIGNED NULL,
                `staff_id` INT UNSIGNED NULL,
                `is_staff` TINYINT(1) NOT NULL DEFAULT 0,
                `is_organizer` TINYINT(1) NOT NULL DEFAULT 0,
                CONSTRAINT
                    FOREIGN KEY (event_id)
                    REFERENCES ' . Entities\Event::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT
                    FOREIGN KEY (staff_id)
                    REFERENCES ' . BooklyLib\Entities\Staff::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\EventTicketType::getTableName() . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `event_id` INT UNSIGNED NOT NULL,
                `title` VARCHAR(64) NULL,
                `quantity` INT NOT NULL,
                `reserved` INT NOT NULL,
                `reserved_ps` INT NOT NULL,
                `price` DECIMAL(10, 2) NOT NULL DEFAULT 0,
                `position` INT NOT NULL DEFAULT 9999,
                CONSTRAINT
                    FOREIGN KEY (event_id)
                    REFERENCES ' . Entities\Event::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE = INNODB
            ' . $charset_collate
        );

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\EventAttendee::getTableName() . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `customer_id` INT UNSIGNED DEFAULT NULL,                
                `ticket_type_id` INT UNSIGNED NOT NULL,
                `code` VARCHAR(255) NOT NULL DEFAULT "",
                `payment_id` INT UNSIGNED DEFAULT NULL,
                `order_id` INT UNSIGNED NULL,
                `checked_in_at` DATETIME DEFAULT NULL,
                CONSTRAINT
                    FOREIGN KEY (ticket_type_id)
                    REFERENCES ' . Entities\EventTicketType::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT
                    FOREIGN KEY (customer_id)
                    REFERENCES ' . BooklyLib\Entities\Customer::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT
                    FOREIGN KEY (payment_id)
                    REFERENCES ' . BooklyLib\Entities\Payment::getTableName() . '(id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE,
                CONSTRAINT
                    FOREIGN KEY (order_id)
                    REFERENCES ' . BooklyLib\Entities\Order::getTableName() . '(id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            ) ENGINE = INNODB
            ' . $charset_collate
        );
    }

}
