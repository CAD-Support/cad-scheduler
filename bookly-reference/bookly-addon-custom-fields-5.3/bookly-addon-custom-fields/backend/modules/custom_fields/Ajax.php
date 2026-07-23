<?php
namespace BooklyCustomFields\Backend\Modules\CustomFields;

use Bookly\Lib as BooklyLib;
use BooklyCustomFields\Lib\Utils\Common;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Save custom fields.
     */
    public static function saveCustomFields()
    {
        $per_service = self::parameter( 'per_service' );
        $merge_repeating = self::parameter( 'merge_repeating' );
        $custom_fields = self::parameter( 'fields', array() );

        foreach ( $custom_fields as &$custom_field ) {
            $custom_field = (object) $custom_field;
            Common::registerTranslationCustomField( $custom_field );
        }

        BooklyLib\Proxy\Files::saveCustomFields( $custom_fields );

        update_option( 'bookly_custom_fields_data', json_encode( $custom_fields ) );
        update_option( 'bookly_custom_fields_per_service', (int) $per_service );
        update_option( 'bookly_custom_fields_merge_repeating', (int) $merge_repeating );
        wp_send_json_success();
    }

    /**
     * Load tab data for custom fields page.
     */
    public static function loadTab()
    {
        $tab = self::parameter( 'tab', 'general' );

        $custom_fields = json_decode( get_option( 'bookly_custom_fields_data', '[]' ) );

        switch ( $tab ) {
            case 'general' :
                $service_dropdown_data = BooklyLib\Utils\Common::getServiceDataForDropDown( 's.type = "simple"' );
                $response = array(
                    'html' => self::renderTemplate( '_general', array(
                        'services_html' => self::renderTemplate( '_services', compact( 'service_dropdown_data' ), false ),
                        'description_html' => self::renderTemplate( '_description', array(), false ),
                    ), false ),
                    'custom_fields' => $custom_fields,
                );
                break;
            default:
                $response = array(
                    'html' => self::renderTemplate( '_conditions', compact( 'custom_fields' ), false ),
                    'custom_fields' => $custom_fields,
                    'custom_fields_conditions' => get_option( 'bookly_custom_fields_conditions', array() ),
                );
                break;
        }

        wp_send_json_success( $response );
    }

    /**
     * Save custom fields conditions
     */
    public static function saveConditions()
    {
        update_option( 'bookly_custom_fields_conditions', self::parameter( 'conditions', array() ) );

        wp_send_json_success();
    }
}