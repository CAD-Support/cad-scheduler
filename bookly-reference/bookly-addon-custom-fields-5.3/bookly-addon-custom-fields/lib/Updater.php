<?php
namespace BooklyCustomFields\Lib;

use Bookly\Lib;

class Updater extends Lib\Base\Updater
{
    public function update_5_0()
    {
        $new_pc_key = 'bookly_custom_fields_purchase_code';
        $old_pc_key = 'bookly_custom_fields_envato_purchase_code';
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

    public function update_3_9()
    {
        delete_option( 'bookly_custom_fields_enabled' );
    }

    public function update_3_0()
    {
        global $sitepress;

        $custom_fields = json_decode( get_option( 'bookly_custom_fields_data', '[]' ), true );
        $names = array();
        foreach ( $custom_fields as $custom_field ) {
            switch ( $custom_field['type'] ) {
                case 'textarea':
                case 'text-content':
                case 'text-field':
                case 'captcha':
                case 'file':
                case 'checkboxes':
                case 'radio-buttons':
                case 'drop-down':
                    $name = 'custom_field_' . $custom_field['id'] . '_%s';
                    $title = sanitize_title( $custom_field['label'] );
                    $key = substr( sprintf( $name, $title ), 0, 160 );
                    $names[ $key ] = sprintf( $name, substr( $title, 0, 160 - strlen( $name ) ) );
                    $name = 'custom_field_' . $custom_field['id'] . '_%s_description';
                    $key = substr( sprintf( $name, $title ), 0, 160 );
                    $new_name = 'custom_field_' . $custom_field['id'] . '_%s_descr';
                    $names[ $key ] = sprintf( $new_name, substr( $title, 0, 160 + 1 - strlen( $new_name ) ) );
                    if ( in_array( $custom_field['type'], array( 'checkboxes', 'radio-buttons', 'drop-down' ) ) ) {
                        $name = 'custom_field_' . $custom_field['id'] . '_%s=%s';
                        foreach ( $custom_field['items'] as $label ) {
                            $label = sanitize_title( $label );
                            $key = substr( sprintf( $name, $title, $label ), 0, 160 );
                            $names[ $key ] = sprintf( $name, substr( $title, 0, 32 ), substr( $label, 0, 160 + 2 - 32 - strlen( $name ) ) );
                        }
                    }
                    break;
            }
        }

        $this->renameL10nStrings( $names, false );
        if ( $sitepress instanceof \SitePress ) {
            add_option( 'bookly_show_wpml_resave_required_notice', '1' );
        }
    }

    public function update_2_8()
    {
        $custom_fields = json_decode( get_option( 'bookly_custom_fields_data', '[]' ), true );
        foreach ( $custom_fields as &$custom_field ) {
            if ( ! isset( $custom_field['description'] ) ) {
                $custom_field['description'] = '';
            }
        }
        update_option( 'bookly_custom_fields_data', json_encode( $custom_fields ) );

        $conditions = get_option( 'bookly_custom_fields_conditions', array() );
        foreach ( $conditions as &$condition ) {
            if ( isset( $condition['value'] ) && ! is_array( $condition['value'] ) ) {
                $condition['value'] = array( $condition['value'] );
            }
        }
        update_option( 'bookly_custom_fields_conditions', $conditions );
    }
}