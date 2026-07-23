<?php
namespace BooklyCustomFields\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Utils\DateTime;
use BooklyCustomFields\Lib\Captcha\Captcha;
use BooklyCustomFields\Backend\Modules\CustomFields\Page;
use BooklyCustomFields\Lib\Utils;

class Local extends BooklyLib\Proxy\CustomFields
{
    /**
     * @inheritDoc
     */
    public static function addBooklyMenuItem()
    {
        $custom_fields = __( 'Custom Fields', 'bookly' );

        add_submenu_page( 'bookly-menu', $custom_fields, $custom_fields, BooklyLib\Utils\Common::getRequiredCapability(),
            Page::pageSlug(), function() { Page::render(); } );
    }

    /**
     * Get custom fields.
     *
     * @param array $exclude
     * @return \stdClass[]
     */
    public static function getAll( $exclude = array() )
    {
        $custom_fields = json_decode( get_option( 'bookly_custom_fields_data', '[]' ) );

        if ( ! empty ( $exclude ) ) {
            $custom_fields = array_filter( $custom_fields, function( $field ) use ( $exclude ) {
                return ! in_array( $field->type, $exclude );
            } );
        }

        return $custom_fields;
    }

    /**
     * Get visible custom fields.
     *
     * @param array $exclude
     * @return \stdClass[]
     */
    public static function getVisible( $exclude = array() )
    {
        $custom_fields = json_decode( get_option( 'bookly_custom_fields_data', '[]' ) );

        if ( ! empty ( $exclude ) ) {
            $custom_fields = array_filter( $custom_fields, function( $field ) use ( $exclude ) {
                return ( ! property_exists( $field, 'visible' ) || $field->visible ) && ! in_array( $field->type, $exclude );
            } );
        }

        return $custom_fields;
    }

    /**
     * Get custom fields.
     *
     * @param array $types
     * @return \stdClass[]
     */
    public static function getOnly( array $types )
    {
        if ( empty ( $types ) ) {
            $custom_fields = array();
        } else {
            $custom_fields = json_decode( get_option( 'bookly_custom_fields_data', '[]' ) );
            $custom_fields = array_filter( $custom_fields, function( $field ) use ( $types ) {
                return in_array( $field->type, $types );
            } );
        }

        return $custom_fields;
    }

    /**
     * Get custom fields for service.
     *
     * @param array $custom_fields
     * @param int $service_id
     * @return array
     */
    public static function filterForService( array $custom_fields, $service_id )
    {
        if ( get_option( 'bookly_custom_fields_per_service' ) ) {
            $service_custom_fields = array();
            $custom_fields_data = json_decode( get_option( 'bookly_custom_fields_data', '[]' ) );
            foreach ( $custom_fields_data as $custom_field ) {
                if ( in_array( $service_id, $custom_field->services ) ) {
                    foreach ( $custom_fields as $field ) {
                        if ( $custom_field->id == $field['id'] ) {
                            $service_custom_fields[] = $field;
                        }
                    }
                }
            }

            return $service_custom_fields;
        }

        return $custom_fields;
    }

    /**
     * Get translated custom fields.
     *
     * @param int $service_id
     * @param bool $translate
     * @param string $language_code
     * @param bool $backend_only
     * @return \stdClass[]
     */
    public static function getTranslated( $service_id = null, $translate = true, $language_code = null, $backend_only = true )
    {
        $custom_fields = json_decode( get_option( 'bookly_custom_fields_data', '[]' ) );
        foreach ( $custom_fields as $key => $custom_field ) {
            if ( ( $backend_only || ( ! property_exists( $custom_field, 'visible' ) || $custom_field->visible ) )
                && ( $service_id === null || in_array( $service_id, $custom_field->services ) )
            ) {
                if ( $translate ) {
                    Utils\Common::translateCustomField( $custom_field, $language_code );
                } else {
                    switch ( $custom_field->type ) {
                        case 'checkboxes':
                        case 'radio-buttons':
                        case 'drop-down':
                            $items = $custom_field->items;
                            foreach ( $items as $i => $label ) {
                                $items[ $i ] = array(
                                    'label' => $label,
                                    'value' => $label,
                                );
                            }
                            $custom_field->items = $items;
                            break;
                    }
                }
            } else {
                unset ( $custom_fields[ $key ] );
            }
        }

        return $custom_fields;
    }

    /**
     * Get custom fields which may have data (no Captcha and Text Content).
     *
     * @return \stdClass[]
     */
    public static function getWhichHaveData()
    {
        return self::getAll( array( 'captcha', 'text-content' ) );
    }

    /**
     * Get custom fields data for given customer appointment.
     *
     * @param BooklyLib\Entities\CustomerAppointment $ca
     * @param bool $translate
     * @param null $locale
     * @param bool $backend_only
     * @return array
     */
    public static function getForCustomerAppointment( BooklyLib\Entities\CustomerAppointment $ca, $translate = false, $locale = null, $backend_only = true )
    {
        return $ca->getId()
            ? self::_getData( json_decode( $ca->getCustomFields(), true ) ?: array(), $translate, $locale, $backend_only )
            : array();
    }

    /**
     * Get custom fields data for given cart item.
     *
     * @param BooklyLib\CartItem $cart_item
     * @param bool $translate
     * @param null $locale
     * @return array
     */
    public static function getForCartItem( BooklyLib\CartItem $cart_item, $translate = false, $locale = null )
    {
        $result = array();
        $fields = self::_getData( $cart_item->getCustomFields(), $translate, $locale );
        foreach ( $fields as $field ) {
            $result[] = $field['label'] . ': ' . $field['value'];
        }

        return $result;
    }

    /**
     * Get custom fields data.
     *
     * @param array $customer_custom_fields
     * @param bool $translate
     * @param null $locale
     * @param bool $backend_only
     * @return array
     */
    private static function _getData( array $customer_custom_fields, $translate = false, $locale = null, $backend_only = true )
    {
        $result = array();
        if ( $customer_custom_fields ) {
            $custom_fields = array();
            $cf = self::getTranslated( null, $translate, $locale, $backend_only );
            foreach ( $cf as $field ) {
                $custom_fields[ $field->id ] = $field;
            }
            $data = BooklyLib\Proxy\Files::setFileNamesForCustomFields( $customer_custom_fields, $custom_fields );
            $format_fields = self::getOnly( array( 'date', 'time' ) ) ?: array();
            foreach ( $data as $customer_custom_field ) {
                if ( array_key_exists( $customer_custom_field['id'], $custom_fields ) ) {
                    $field = $custom_fields[ $customer_custom_field['id'] ];
                    $translated_value = array();
                    if ( array_key_exists( 'value', $customer_custom_field ) ) {
                        // Custom field have items ( radio group, etc. )
                        if ( property_exists( $field, 'items' ) ) {
                            foreach ( $field->items as $item ) {
                                // Customer select many values ( checkbox )
                                if ( is_array( $customer_custom_field['value'] ) ) {
                                    foreach ( $customer_custom_field['value'] as $field_value ) {
                                        if ( $item['value'] == $field_value ) {
                                            $translated_value[] = $item['label'];
                                        }
                                    }
                                } elseif ( $item['value'] == $customer_custom_field['value'] ) {
                                    $translated_value[] = $item['label'];
                                }
                            }
                        } else {
                            $value = $customer_custom_field['value'];
                            if ( $value != '' ) {
                                foreach ( $format_fields as $ff ) {
                                    if ( $ff->id == $customer_custom_field['id'] ) {
                                        switch ( $ff->type ) {
                                            case 'time':
                                                $value = DateTime::formatTime( $customer_custom_field['value'] );
                                                break;
                                            case 'date':
                                                $value = DateTime::formatDate( $customer_custom_field['value'] );
                                                break;
                                        }
                                        break;
                                    }
                                }
                            }
                            $translated_value[] = $value;
                        }
                    }
                    $result[] = array(
                        'id' => $customer_custom_field['id'],
                        'label' => $field->label,
                        'value' => implode( ', ', $translated_value ),
                        'type' => $field->type
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Get formatted custom fields.
     *
     * @param BooklyLib\Entities\CustomerAppointment $ca
     * @param string $format
     * @param string $locale
     * @return string
     */
    public static function getFormatted( BooklyLib\Entities\CustomerAppointment $ca, $format, $locale = null )
    {
        $result = '';
        switch ( $format ) {
            case 'html':
                foreach ( self::getForCustomerAppointment( $ca, true, $locale ) as $custom_field ) {
                    if ( $custom_field['value'] != '' ) {
                        $result .= sprintf(
                            '<tr valign=top><td>%s:&nbsp;</td><td>%s</td></tr>',
                            $custom_field['label'], $custom_field['value']
                        );
                    }
                }
                if ( $result != '' ) {
                    $result = "<table cellspacing=0 cellpadding=0 border=0>$result</table>";
                }
                break;

            case 'text':
                foreach ( self::getForCustomerAppointment( $ca, true, $locale ) as $custom_field ) {
                    if ( $custom_field['value'] != '' ) {
                        $result .= sprintf(
                            "%s: %s\n",
                            $custom_field['label'], $custom_field['value']
                        );
                    }
                }
                break;
        }

        return $result;
    }

    /**
     * Validate custom fields.
     *
     * @param array $errors
     * @param string $value
     * @param int $form_id
     * @param int $cart_key
     * @return array
     */
    public static function validate( array $errors, $value, $form_id, $cart_key )
    {
        $decoded_value = json_decode( $value );
        $fields = array();
        foreach ( json_decode( get_option( 'bookly_custom_fields_data' ) ) as $field ) {
            $fields[ $field->id ] = $field;
        }

        foreach ( $decoded_value as $field ) {
            if ( isset ( $fields[ $field->id ] ) ) {
                if ( ( $fields[ $field->id ]->type === 'captcha' ) && ! Captcha::validate( $form_id, $field->value ) ) {
                    $errors['custom_fields'][ $cart_key ][ $field->id ] = __( 'Incorrect code', 'bookly' );
                } elseif ( ( $fields[ $field->id ]->type === 'number' ) && $fields[ $field->id ]->limits && ( $fields[ $field->id ]->min > $field->value || $fields[ $field->id ]->max < $field->value ) ) {
                    $errors['custom_fields'][ $cart_key ][ $field->id ] = __( 'Incorrect value', 'bookly' );
                } elseif ( ( $fields[ $field->id ]->type === 'time' ) && $fields[ $field->id ]->limits && ! empty( $field->value ) && ( DateTime::timeToSeconds( $fields[ $field->id ]->min ) > DateTime::timeToSeconds( $field->value ) || DateTime::timeToSeconds( $fields[ $field->id ]->max ) < DateTime::timeToSeconds( $field->value ) ) ) {
                    $errors['custom_fields'][ $cart_key ][ $field->id ] = __( 'Incorrect value', 'bookly' );
                } elseif ( ( $fields[ $field->id ]->type === 'date' ) && $fields[ $field->id ]->limits && ! empty( $field->value ) && ( strtotime( $fields[ $field->id ]->min ) > strtotime( $field->value ) || strtotime( $fields[ $field->id ]->max ) < strtotime( $field->value ) ) ) {
                    $errors['custom_fields'][ $cart_key ][ $field->id ] = __( 'Incorrect value', 'bookly' );
                } elseif ( property_exists( $fields[ $field->id ], 'required' ) && $fields[ $field->id ]->required && ( ! property_exists( $field, 'value' ) || ( empty ( $field->value ) && $field->value != '0' ) ) ) {
                    foreach ( $decoded_value as $cf ) {
                        // Only for visible custom fields based on their conditions,
                        // validation required should be reported
                        if ( $field->id === $cf->id ) {
                            $errors['custom_fields'][ $cart_key ][ $field->id ] = __( 'Required', 'bookly' );
                            break;
                        }
                    }
                } else {
                    /**
                     * Custom field validation for a third party,
                     * if the value is not valid then please add an error message like in the above example.
                     *
                     * @param \stdClass
                     * @param ref array
                     * @param string
                     * @param \stdClass
                     */
                    do_action_ref_array( 'bookly_validate_custom_field', array( $field, &$errors, $cart_key, $fields[ $field->id ] ) );
                }
            }
        }

        return $errors;
    }
}