<?php
namespace BooklyCustomFields\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Proxy;
use Bookly\Frontend\Components\Booking\InfoText;
use BooklyCustomFields\Lib;
use BooklyCustomFields\Lib\Captcha\Captcha;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareInfoTextCodes( array $codes, array $data )
    {
        $codes['custom_fields'] = isset( $data['custom_fields'] ) ? implode( '<br>', $data['custom_fields'] ) : '';
        foreach ( $data as $key => $value ) {
            if ( strpos( $key, 'custom_field#' ) === 0 ) {
                $codes[ $key ] = $value;
            }
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCartItemInfoText( $data, BooklyLib\CartItem $cart_item )
    {
        $last = count( $data['appointments'] ) - 1;
        $custom_fields = Lib\ProxyProviders\Local::getForCartItem( $cart_item, true );
        $fields = array();
        foreach ( Lib\ProxyProviders\Local::getWhichHaveData() as $field ) {
            $fields[ $field->id ] = $field;
        }
        $data['appointments'][ $last ]['custom_fields'] = InfoText::implode( $custom_fields );
        $data['custom_fields'] = array_key_exists( 'custom_fields', $data )
            ? array_merge( $data['custom_fields'], $custom_fields )
            : $custom_fields;
        foreach ( $cart_item->getCustomFields() as $custom_field ) {
            if ( isset( $custom_field['value'] ) ) {
                $value = is_array( $custom_field['value'] ) ? implode( ',', $custom_field['value'] ) : $custom_field['value'];
                if ( $value && isset( $fields[ $custom_field['id'] ] ) ) {
                    switch ( $fields[ $custom_field['id'] ]->type ) {
                        case 'time':
                            $value = BooklyLib\Utils\DateTime::formatTime( $value );
                            break;
                        case 'date':
                            $value = BooklyLib\Utils\DateTime::formatDate( $value );
                            break;
                    }
                }
            } else {
                $value = '';
            }
            $data[ 'custom_field#' . $custom_field['id'] ] = $value;
            $data['appointments'][ $last ][ 'custom_field#' . $custom_field['id'] ] = $value;
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public static function renderCustomFieldsOnDetailsStep( BooklyLib\UserBookingData $userData )
    {
        $cf_data = array();
        $form_id = $userData->getFormId();
        if ( BooklyLib\Config::customFieldsPerService() ) {
            // Prepare custom fields data per service.
            foreach ( $userData->cart->getItems() as $cart_key => $cart_item ) {
                $data = array();
                $service_id = $cart_item->getServiceId();
                $key = get_option( 'bookly_custom_fields_merge_repeating' ) ? $service_id : $cart_key;

                if ( ! isset( $cf_data[ $key ] ) ) {
                    foreach ( $cart_item->getCustomFields() as $field ) {
                        if ( isset( $field['visible'] ) && $field['visible'] ) {
                            $data[ $field['id'] ] = $field['value'];
                        }
                    }
                    if ( $cart_item->getService()->withSubServices() ) {
                        $cf_list = array();
                        // Collect custom fields for compound service.
                        foreach ( $cart_item->getService()->getSubServices() as $sub_service ) {
                            foreach ( Lib\ProxyProviders\Local::getTranslated( $sub_service->getId() ) as $field ) {
                                if ( ! array_key_exists( $field->id, $cf_list ) && property_exists( $field, 'visible' ) && $field->visible ) {
                                    $cf_list[ $field->id ] = $field;
                                }
                            }
                        }
                        $custom_fields = array();
                        foreach ( Lib\ProxyProviders\Local::getAll() as $cf ) {
                            if ( isset( $cf_list[ $cf->id ] ) ) {
                                $custom_fields[] = $cf_list[ $cf->id ];
                            }
                        }
                    } else {
                        $custom_fields = Lib\ProxyProviders\Local::getTranslated( $service_id, true, null, false );
                    }
                    $cf_data[ $key ] = array(
                        'service_title' => BooklyLib\Entities\Service::find( $cart_item->getServiceId() )->getTranslatedTitle(),
                        'custom_fields' => $custom_fields,
                        'data' => $data,
                    );
                }
            }
        } else {
            $cart_items = $userData->cart->getItems();
            $cart_item = array_pop( $cart_items );
            $data = array();
            foreach ( $cart_item->getCustomFields() as $field ) {
                $data[ $field['id'] ] = $field['value'];
            }
            $custom_fields = Lib\ProxyProviders\Local::getTranslated( null, true, null, false );
            $cf_data[] = compact( 'custom_fields', 'data' );
        }

        if ( ! BooklyLib\Config::filesActive() ) {
            foreach ( $cf_data as &$value ) {
                $value['custom_fields'] = array_filter( $value['custom_fields'], function( $field ) {
                    return $field->type !== 'file';
                } );
            }
        }

        if ( strpos( get_option( 'bookly_custom_fields_data' ), '"captcha"' ) !== false ) {
            $userData->sessionSave();
            // Init Captcha.
            Captcha::init( $form_id );
            $userData->load();
        }

        $show_service_title = BooklyLib\Config::customFieldsPerService() && count( $cf_data ) > 1;

        $captcha_url = admin_url( sprintf(
            'admin-ajax.php?action=bookly_custom_fields_captcha&csrf_token=%s&form_id=%s&%f',
            BooklyLib\Utils\Common::getCsrfToken(),
            $form_id,
            microtime( true )
        ) );
        $conditional_fields = array();
        foreach ( get_option( 'bookly_custom_fields_conditions', array() ) as $condition ) {
            $conditional_fields[] = $condition['target'];
        }

        self::renderTemplate( '6_details', compact( 'cf_data', 'show_service_title', 'captcha_url', 'conditional_fields', 'form_id' ) );
    }

    /**
     * @inheritDoc
     */
    public static function stepOptions( $options, $step, $userData )
    {
        if ( $step == 'details' ) {
            $options['custom_fields_conditions'] = get_option( 'bookly_custom_fields_conditions', array() );
        }

        return $options;
    }
}