<?php
namespace BooklyCustomFields\Frontend\Modules\ModernBookingForm\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCustomFields\Lib;
use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\Request;

class Shared extends \Bookly\Frontend\Modules\ModernBookingForm\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareFormOptions( $bookly_options )
    {
        $bookly_options['custom_fields'] = array(
            'data' => array_values( Lib\ProxyProviders\Local::getTranslated( null, true, null, false ) ),
            'conditions' => get_option( 'bookly_custom_fields_conditions', array() ),
            'merge' => get_option( 'bookly_custom_fields_merge_repeating' ),
            'bind_services' => get_option( 'bookly_custom_fields_per_service' )
        );
        if ( in_array( 'captcha', array_map( function( $field ) { return $field->type; }, Lib\ProxyProviders\Local::getVisible() ) ) ) {
            Lib\Captcha\Captcha::init( $bookly_options['session_id'] );
        }

        $bookly_options['time_slot_length'] = (int) get_option( 'bookly_gen_time_slot_length' );

        return $bookly_options;
    }

    /**
     * @inheritDoc
     */
    public static function validate( Request $request )
    {
        if ( in_array( 'custom_fields', $request->getSettings()->get( 'details_fields_show' ), true ) ) {
            $custom_fields_errors = array();
            foreach ( $request->getCustomFields() as $index => $custom_fields ) {
                $errors = Lib\ProxyProviders\Local::validate( array(), json_encode( $custom_fields ), $request->getSessionId(), $index );
                if ( $errors ) {
                    foreach ( $errors['custom_fields'][ $index ] as $id => $error ) {
                        $custom_fields_errors[ $index ][ $id ] = $error;
                    }
                }
            }
            if ( $custom_fields_errors ) {
                $request->addNotice( array( 'custom_fields' => $custom_fields_errors ) );
            }
        }
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearanceData( array $bookly_options )
    {
        $custom_fields = array();
        foreach ( Lib\ProxyProviders\Local::getTranslated() ?: array() as $custom_field ) {
            if ( ! property_exists( $custom_field, 'visible' ) || $custom_field->visible ) {
                $custom_fields[] = array(
                    'id' => $custom_field->id,
                    'label' => $custom_field->label
                );
            }
        }
        $bookly_options['details_fields']['custom_fields'] = __( 'Custom fields', 'bookly' );
        $bookly_options['fields']['custom_fields'] = __( 'Custom fields', 'bookly' );
        $bookly_options['custom_fields'] = $custom_fields;

        return $bookly_options;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareAppearance( array $bookly_options )
    {
        foreach ( Lib\ProxyProviders\Local::getTranslated() ?: array() as $custom_field ) {
            if ( ! property_exists( $custom_field, 'visible' ) || $custom_field->visible ) {
                if ( ! isset( $bookly_options['details_fields_width']['custom_fields'][ $custom_field->id ] ) ) {
                    $bookly_options['details_fields_width']['custom_fields'][ $custom_field->id ] = 12;
                }
            }
        }

        return $bookly_options;
    }
}