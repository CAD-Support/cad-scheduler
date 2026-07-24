<?php
namespace BooklyDepositPayments\Lib\Notifications\Assets\Order\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Notifications\Assets\Order\Codes;
use Bookly\Lib\Notifications\Assets\Order\Proxy;

abstract class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCodes( Codes $codes )
    {
        if ( $codes->getOrder()->hasPayment() ) {
            $details = $codes->getOrder()->getPayment()->getDetailsData();
            $codes->deposit_value = $details->getDeposit() ?: '';
        } else {
            $codes->deposit_value = '';
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareReplaceCodes( array $replace_codes, Codes $codes, $format )
    {
        $replace_codes['deposit_value'] = BooklyLib\Utils\Price::format( $codes->deposit_value );

        return $replace_codes;
    }
}