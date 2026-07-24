<?php
namespace BooklyCustomerInformation\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCustomerInformation\Lib;
use BooklyCustomerInformation\Backend\Modules\CustomerInformation\Page;
use BooklyCustomerInformation\Frontend\Modules\CustomerCabinet;
use Bookly\Lib\Utils\DateTime;

class Local extends BooklyLib\Proxy\CustomerInformation
{
    /**
     * @inheritDoc
     */
    public static function addBooklyMenuItem()
    {
        $customer_information = __( 'Customer Information', 'bookly' );

        add_submenu_page( 'bookly-menu', $customer_information, $customer_information, BooklyLib\Utils\Common::getRequiredCapability(),
            Page::pageSlug(), function() { Page::render(); } );
    }

    /**
     * @inheritDoc
     */
    public static function getFields( $exclude = array() )
    {
        $fields = json_decode( get_option( 'bookly_customer_information_data', '[]' ) );

        if ( ! empty ( $exclude ) ) {
            $fields = array_filter( $fields, function( $field ) use ( $exclude ) {
                return ! in_array( $field->type, $exclude );
            } );
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public static function getOnlyFields( array $types )
    {
        if ( empty ( $types ) ) {
            $fields = array();
        } else {
            $fields = json_decode( get_option( 'bookly_customer_information_data', '[]' ) );
            $fields = array_filter( $fields, function( $field ) use ( $types ) {
                return in_array( $field->type, $types );
            } );
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public static function getFieldsWhichMayHaveData()
    {
        return self::getFields( array( 'text-content' ) );
    }

    /**
     * @inheritDoc
     */
    public static function getTranslatedFields( $language_code = null )
    {
        $fields = self::getFields();

        foreach ( $fields as $field ) {
            Lib\Utils\Common::translateField( $field, $language_code );
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public static function validate( array $errors, array $values )
    {
        $info_fields = array();
        foreach ( self::getFieldsWhichMayHaveData() as $field ) {
            $info_fields[ $field->id ] = $field;
        }

        foreach ( $values as $field ) {
            $field_id = $field['id'];
            if ( isset ( $info_fields[ $field_id ] ) ) {
                $info_field = $info_fields[ $field_id ];
                if ( array_key_exists( 'value', $field ) ) {
                    $field_value = $field['value'];
                } else {
                    $field_value = null;
                }
                if ( ( $info_field->type === 'number' ) && $info_field->limits && ( $info_field->min > $field_value || $info_field->max < $field_value ) ) {
                    $errors['info_fields'][ $field_id ] = __( 'Incorrect value', 'bookly' );
                } elseif ( ( $info_field->type === 'time' ) && $info_field->limits && ! empty( $field_value ) && ( DateTime::timeToSeconds( $info_field->min ) > DateTime::timeToSeconds( $field_value ) || DateTime::timeToSeconds( $info_field->max ) < DateTime::timeToSeconds( $field_value ) ) ) {
                    $errors['info_fields'][ $field_id ] = __( 'Incorrect value', 'bookly' );
                } elseif ( ( $info_field->type === 'date' ) && $info_field->limits && ! empty( $field_value ) && ( strtotime( $info_field->min ) > strtotime( $field_value ) || strtotime( $info_field->max ) < strtotime( $field_value ) ) ) {
                    $errors['info_fields'][ $field_id ] = __( 'Incorrect value', 'bookly' );
                } elseif ( property_exists( $info_field, 'required' ) && $info_field->required && empty ( $field_value ) && $field_value != '0' ) {
                    foreach ( $values as $f ) {
                        // Only for visible info fields based on their conditions,
                        // validation required should be reported
                        if ( $field_id === $f['id'] ) {
                            $errors['info_fields'][ $field_id ] = __( 'Required', 'bookly' );
                            break;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @inheritDoc
     */
    public static function renderCustomerCabinet( $field_id, BooklyLib\Entities\Customer $customer )
    {
        static $info_fields;
        if ( $info_fields === null ) {
            $info_fields = self::getValues( json_decode( $customer->getInfoFields(), true ) );
        }
        foreach ( $info_fields as $field ) {
            if ( $field->id == $field_id ) {
                CustomerCabinet\Components::render( $field );
                break;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareInfoFields( array $info_fields )
    {
        return Lib\Utils\Common::prepareInfoFields( $info_fields );
    }

    /**
     * @inheritDoc
     */
    public static function prepareVisibleInfoFields( array $info_fields, $customer )
    {
        $customer_info_fields = json_decode( $customer->getInfoFields() );
        foreach ( self::getFieldsWhichMayHaveData() as $field ) {
            if ( ! property_exists( $field, 'visible' ) || ! $field->visible ) {
                foreach ( $customer_info_fields as $customer_info_field ) {
                    if ( $customer_info_field->id == $field->id ) {
                        $info_fields[] = array(
                            'id' => $field->id,
                            'value' => $customer_info_field->value,
                        );
                        break;
                    }
                }
            } else {
                foreach ( $info_fields as &$info_field ) {
                    if ( property_exists( $field, 'items' ) && $info_field['id'] == $field->id ) {
                        if ( is_array( $info_field['value'] ) ) {
                            foreach ( $info_field['value'] as &$f_value ) {
                                if ( ! in_array( $f_value, $field->items ) && in_array( html_entity_decode( $f_value ), $field->items ) ) {
                                    $f_value = html_entity_decode( $f_value );
                                }
                            }
                        } elseif ( ! in_array( $info_field['value'], $field->items ) && in_array( html_entity_decode( $info_field['value'] ), $field->items ) ) {
                            $info_field['value'] = html_entity_decode( $info_field['value'] );
                        }
                    }
                }
            }
        }

        return $info_fields;
    }

    /**
     * @inerhitDoc
     */
    public static function setFromCookies( BooklyLib\UserBookingData $userData )
    {
        $info_fields = array();
        if ( isset( $_COOKIE['bookly-customer-info-fields'] ) ) {
            foreach ( self::getValues( json_decode( stripslashes( $_COOKIE['bookly-customer-info-fields'] ), true ) ?: array() ) as $field ) {
                $info_fields[] = array(
                    'id' => $field->id,
                    'value' => $field->value,
                );
            }
        }

        return $userData->setInfoFields( $info_fields );
    }

    /**
     * @param array $info_fields
     * @return \stdClass[]
     */
    private static function getValues( array $info_fields )
    {
        $fields = self::getFieldsWhichMayHaveData();
        foreach ( $fields as $field ) {
            $field->value = $field->type === 'checkboxes' ? array() : '';
            foreach ( $info_fields as $value ) {
                if ( $value['id'] == $field->id ) {
                    $field->value = $value['value'];
                }
            }
        }

        return $fields;
    }
}