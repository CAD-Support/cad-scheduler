<?php
namespace BooklyCart\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareOptions( array $options_to_save, array $options )
    {
        return array_merge( $options_to_save, array_intersect_key( $options, array_flip( array (
            'bookly_cart_enabled',
            'bookly_app_button_book_more_near_next',
            'bookly_l10n_button_book_more',
            'bookly_l10n_info_cart_step',
            'bookly_l10n_step_cart',
            'bookly_l10n_step_cart_button_next',
            'bookly_l10n_step_cart_slot_not_available',
        ) ) ) );
    }
}