<?php
namespace BooklyCoupons\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareOptions( array $options_to_save, array $options )
    {
        return array_merge( $options_to_save, array_intersect_key( $options, array_flip( array(
            'bookly_coupons_enabled',
            'bookly_l10n_info_coupon_single_app',
            'bookly_l10n_info_coupon_several_apps',
            'bookly_l10n_label_coupon',
            'bookly_l10n_coupon_error_invalid',
            'bookly_l10n_coupon_error_expired',
        ) ) ) );
    }
}