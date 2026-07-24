<?php
namespace BooklyDepositPayments\Backend\Modules\Notifications\ProxyProviders;

use Bookly\Backend\Modules\Notifications\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareNotificationCodes( array $codes, $type )
    {
        $codes['payment']['amount_due'] = array( 'description' => __( 'Amount due', 'bookly' ) );
        $codes['payment']['amount_paid'] = array( 'description' => __( 'Amount paid', 'bookly' ) );
        $codes['payment']['deposit_value'] = array( 'description' => __( 'Total deposit amount to be paid', 'bookly' ) );

        return $codes;
    }
}