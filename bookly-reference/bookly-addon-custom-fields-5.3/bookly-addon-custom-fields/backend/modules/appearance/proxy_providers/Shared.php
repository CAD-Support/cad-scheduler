<?php
namespace BooklyCustomFields\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;
use BooklyCustomFields\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareOptions( array $options_to_save, array $options )
    {
        return array_merge( $options_to_save, array_intersect_key( $options, array_flip( array(
            'bookly_custom_fields_enabled',
        ) ) ) );
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( array $codes )
    {
        $codes['appointments']['loop']['codes']['custom_fields'] = array( 'description' => __( 'Combined values of all custom fields', 'bookly' ), 'if' => true );
        $codes['custom_fields'] = array( 'description' => __( 'Combined values of all custom fields', 'bookly' ), 'if' => true );
        foreach ( Lib\ProxyProviders\Local::getAll( array( 'captcha', 'text-content', 'file' ) ) as $custom_field ) {
            $codes['appointments']['loop']['codes'][ 'custom_field#' . $custom_field->id ] = array( 'description' => __( 'Custom field', 'bookly' ) . ': ' . $custom_field->label, 'if' => true );
            $codes[ 'custom_field#' . $custom_field->id ] = array( 'description' => __( 'Custom field', 'bookly' ) . ': ' . $custom_field->label, 'if' => true );
        }

        return $codes;
    }
}