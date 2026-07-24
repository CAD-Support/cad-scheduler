<?php
namespace BooklyDepositPayments\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareOptions( array $options_to_save, array $options )
    {
        return array_merge( $options_to_save, array_intersect_key( $options, array_flip( array(
            'bookly_l10n_info_deposit',
            'bookly_l10n_label_deposit_payment',
            'bookly_l10n_label_full_payment',
        ) ) ) );
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( array $codes )
    {
        return array_merge( $codes, array(
            'amount_due' => array( 'description' => __( 'Amount due', 'bookly' ), 'if' => true ),
            'amount_to_pay' => array( 'description' => __( 'Amount to pay', 'bookly' ), 'if' => true ),
            'deposit_value' => array( 'description' => _x( 'Deposit value', 'portion of the payment', 'bookly' ), 'if' => true ),
        ) );
    }
}