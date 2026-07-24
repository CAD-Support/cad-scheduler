<?php
namespace BooklyCustomFields\Lib\Utils;

use BooklyCustomFields\Lib;

abstract class Common
{
    /**
     * Get string translated with WPML.
     *
     * @param \stdClass $custom_field
     * @param null|string $language_code Return the translation in this language
     * @return \stdClass string
     */
    public static function translateCustomField( $custom_field, $language_code )
    {
        $names = self::getWpmlStingNames( $custom_field );
        $custom_field->label = apply_filters( 'wpml_translate_single_string', $custom_field->label, 'bookly', $names['label'], $language_code );
        $custom_field->description = apply_filters( 'wpml_translate_single_string', $custom_field->description, 'bookly', $names['descr'], $language_code );
        switch ( $custom_field->type ) {
            case 'checkboxes':
            case 'radio-buttons':
            case 'drop-down':
            foreach ( $names['items'] as $position => $value ) {
                $text = $custom_field->items[ $position ];
                $custom_field->items[ $position ] = array(
                    'label' => apply_filters( 'wpml_translate_single_string', $text, 'bookly', $value, $language_code ),
                    'value' => $text,
                );
            }
        }

        return $custom_field;
    }

    /**
     * @param \stdClass $custom_field
     * @return void
     */
    public static function registerTranslationCustomField( $custom_field )
    {
        $names = self::getWpmlStingNames( $custom_field );
        do_action( 'wpml_register_single_string', 'bookly', $names['label'], $custom_field->label );
        do_action( 'wpml_register_single_string', 'bookly', $names['descr'], $custom_field->description );
        switch ( $custom_field->type ) {
            case 'checkboxes':
            case 'radio-buttons':
            case 'drop-down':
                foreach ( $names['items'] as $position => $value ) {
                    apply_filters( 'wpml_register_single_string', 'bookly', $value, $custom_field->items[ $position ] );
                }
                break;
        }
    }

    /**
     * @param \stdClass $custom_field
     * @return array
     */
    private static function getWpmlStingNames( $custom_field )
    {
        $wpml_name_length = 160;
        $name = 'custom_field_' . $custom_field->id . '_%s';
        $label = self::sanitize( $custom_field->label );
        $descr = 'custom_field_' . $custom_field->id . '_%s_descr';
        $names = array(
            'label' => sprintf( $name, substr( $label, 0, $wpml_name_length + 1 - strlen( $name ) ) ),
            'descr' => sprintf( $descr, substr( $label, 0, $wpml_name_length + 1 - strlen( $descr ) ) ),
            'items' => array(),
        );

        switch ( $custom_field->type ) {
            case 'checkboxes':
            case 'radio-buttons':
            case 'drop-down':
                $item_name = 'custom_field_' . $custom_field->id . '_%s=%s';
                foreach ( $custom_field->items as $value ) {
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