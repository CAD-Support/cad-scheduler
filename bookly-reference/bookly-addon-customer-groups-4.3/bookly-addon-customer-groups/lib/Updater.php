<?php
namespace BooklyCustomerGroups\Lib;

use Bookly\Lib;

class Updater extends Lib\Base\Updater
{
    public function update_4_1()
    {
        $new_pc_key = 'bookly_customer_groups_purchase_code';
        $old_pc_key = 'bookly_customer_groups_envato_purchase_code';
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

    public function update_3_7()
    {
        global $wpdb;

        $disposable_options[] = $this->disposable( __FUNCTION__ . '-customer-groups-rename-gateways', function ( $self ) use ( $wpdb ) {
            $table_name = $self->getTableName( 'bookly_customer_groups' );
            foreach ( $wpdb->get_results( 'SELECT id, gateways FROM `' . $table_name . '` WHERE gateways != \'[]\'' ) as $record ) {
                $gateways = str_replace( 'bookly-addon-', '', $record->gateways );
                $wpdb->query( $wpdb->prepare( 'UPDATE `' . $table_name . '` SET `gateways` = %s WHERE id = %d', $gateways, $record->id ) );
            }
        } );

        $disposable_options[] = $this->disposable( __FUNCTION__ . '-rename-gateways', function ( $self ) {
            $settings = get_option( 'bookly_customer_groups_general_settings' );
            if ( isset( $settings['gateways'] ) ) {
                foreach ( $settings['gateways'] as $id => $gateway ) {
                    $settings['gateways'][ $id ] = str_replace( 'bookly-addon-', '', $gateway );
                }
                update_option( 'bookly_customer_groups_general_settings', $settings );
            }
        } );

        foreach ( $disposable_options as $option_name ) {
            delete_option( $option_name );
        }
    }

    public function update_2_8()
    {
        $this->alterTables( array(
            'bookly_customer_groups' => array(
                'ALTER TABLE `%s` ADD COLUMN `gateways` VARCHAR(255) DEFAULT NULL',
            ),
        ) );

        add_option( 'bookly_customer_groups_general_settings', array(
            'status' => get_option( 'bookly_appointment_default_status', 'approved' ),
            'skip_payment' => '0',
            'gateways' => null,
            'discount' => '0',
        ) );
    }

    public function update_2_6()
    {
        $this->alterTables( array(
            'bookly_customer_groups' => array(
                'ALTER TABLE `%s` ADD COLUMN `skip_payment` TINYINT(1) NOT NULL DEFAULT 0 AFTER `discount`',
            ),
        ) );

        $options = array(
            'bookly_l10n_info_complete_step_group_skip_payment' => __( 'Thank you! Your booking is complete. An email with details of your booking has been sent to you.', 'bookly' ),
        );
        $this->addL10nOptions( $options );
    }

    public function update_1_7()
    {
        $this->upgradeCharsetCollate( array(
            'bookly_customer_groups',
            'bookly_customer_groups_services',
        ) );
    }

    public function update_1_4()
    {
        global $wpdb;

        // Rename tables.
        $tables = array(
            'customer_groups',
            'customer_groups_services',
        );
        $query = 'RENAME TABLE ';
        foreach ( $tables as $table ) {
            $query .= sprintf( '`%s` TO `%s`, ', $this->getTableName( 'ab_' . $table ), $this->getTableName( 'bookly_' . $table ) );
        }
        $query = substr( $query, 0, -2 );
        $wpdb->query( $query );

        delete_option( 'bookly_customer_groups_enabled' );
    }
}