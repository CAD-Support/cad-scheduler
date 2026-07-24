<?php
namespace BooklyChainAppointments\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;
use BooklyChainAppointments\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareOptions( array $options_to_save, array $options )
    {
        return array_merge( $options_to_save, array_intersect_key( $options, array_flip( array (
            'bookly_chain_appointments_enabled',
            'bookly_l10n_chain_appointments_book_more',
        ) ) ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderServiceStepSettings()
    {
        self::renderTemplate( 'appearance_settings' );
    }
}