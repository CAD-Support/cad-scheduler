<?php
namespace BooklyCustomerInformation\Lib\Utils;

use BooklyCustomerInformation\Lib;

abstract class Common
{
    /**
     * Prepare customer information fields
     * @param array $fields_data ['id'=>'value']
     * @return array
     */
    public static function prepareInfoFields( array $fields_data )
    {
        $info_fields = array();

        foreach ( Lib\ProxyProviders\Local::getFieldsWhichMayHaveData() as $field ) {
            if ( array_key_exists( $field->id, $fields_data ) ) {
                $info_field = $fields_data[ $field->id ];
            } else {
                $info_field = array( 'id' => $field->id );
            }
            if ( ! isset ( $info_field['value'] ) ) {
                $info_field['value'] = $field->type === 'checkboxes' ? array() : '';
            }
            $info_fields[] = $info_field;
        }

        return $info_fields;
    }

    /**
     * @param \stdClass $info_field
     * @return void
     */
    public static function registerTranslationInfoField( $info_field )
    {
        $names = self::getWpmlStingNames( $info_field );
        do_action( 'wpml_register_single_string', 'bookly', $names['label'], $info_field->label );
        do_action( 'wpml_register_single_string', 'bookly', $names['descr'], $info_field->description );
        switch ( $info_field->type ) {
            case 'checkboxes':
            case 'radio-buttons':
            case 'drop-down':
                foreach ( $names['items'] as $position => $value ) {
                    apply_filters( 'wpml_register_single_string', 'bookly', $value, $info_field->items[ $position ] );
                }
                break;
        }
    }

    /**
     * Get string translated with WPML.
     *
     * @param \stdClass $customer_field
     * @param null|string $language_code Return the translation in this language
     * @return \stdClass string
     */
    public static function translateField( $customer_field, $language_code )
    {
        $names = self::getWpmlStingNames( $customer_field );
        $customer_field->label = apply_filters( 'wpml_translate_single_string', $customer_field->label, 'bookly', $names['label'], $language_code );
        $customer_field->description = apply_filters( 'wpml_translate_single_string', $customer_field->description, 'bookly', $names['descr'], $language_code );
        switch ( $customer_field->type ) {
            case 'checkboxes':
            case 'radio-buttons':
            case 'drop-down':
                foreach ( $names['items'] as $position => $value ) {
                    $text = $customer_field->items[ $position ];
                    $customer_field->items[ $position ] = array(
                        'label' => apply_filters( 'wpml_translate_single_string', $text, 'bookly', $value, $language_code ),
                        'value' => $text,
                    );
                }
        }

        return $customer_field;
    }

    /**
     * @param \stdClass $customer_field
     * @return array
     */
    private static function getWpmlStingNames( $customer_field )
    {
        $wpml_name_length = 160;
        $name = 'customer_field_' . $customer_field->id . '_%s';
        $label = self::sanitize( $customer_field->label );
        $descr = 'customer_field_' . $customer_field->id . '_%s_descr';
        $names = array(
            'label' => sprintf( $name, substr( $label, 0, $wpml_name_length + 1 - strlen( $name ) ) ),
            'descr' => sprintf( $descr, substr( $label, 0, $wpml_name_length + 1 - strlen( $descr ) ) ),
            'items' => array(),
        );

        switch ( $customer_field->type ) {
            case 'checkboxes':
            case 'radio-buttons':
            case 'drop-down':
                $item_name = 'customer_field_' . $customer_field->id . '_%s=%s';
                foreach ( $customer_field->items as $value ) {
                    $value = self::sanitize( $value );
                    $names['items'][] = sprintf( $item_name, substr( $label, 0, 32 ), substr( $value, 0, $wpml_name_length - 30 - strlen( $item_name ) ) );
                }
                break;
        }

        return $names;
    }

    /**
     * @param string $text
     * @return string
     */
    private static function sanitize( $text )
    {
        $chr = chr( 195 );
        $chars = array(
            $chr . chr( 132 ) => 'Ae',
            $chr . chr( 133 ) => 'Aa',
            $chr . chr( 134 ) => 'Ae',
            $chr . chr( 150 ) => 'Oe',
            $chr . chr( 152 ) => 'Oe',
            $chr . chr( 156 ) => 'Ue',
            $chr . chr( 159 ) => 'ss',
            $chr . chr( 164 ) => 'ae',
            $chr . chr( 165 ) => 'aa',
            $chr . chr( 166 ) => 'ae',
            $chr . chr( 182 ) => 'oe',
            $chr . chr( 184 ) => 'oe',
            $chr . chr( 188 ) => 'ue',
        );

        return sanitize_title( strtr( $text, $chars ) );
    }
}