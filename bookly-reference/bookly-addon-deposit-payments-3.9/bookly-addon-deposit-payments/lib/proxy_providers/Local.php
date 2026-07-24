<?php
namespace BooklyDepositPayments\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyDepositPayments\Lib;
use Bookly\Lib\Proxy\DepositPayments as DepositPaymentsProxy;

class Local extends DepositPaymentsProxy
{
    /**
     * Format deposit.
     *
     * @param double $deposit_amount
     * @param string $deposit
     * @return string
     */
    public static function formatDeposit( $deposit_amount, $deposit )
    {
        return Lib\Utils\Common::formatDeposit( $deposit_amount, $deposit );
    }

    /**
     * Get deposit amount.
     *
     * @param double $amount
     * @param string $deposit
     * @param int    $number_of_persons
     * @return double|string
     */
    public static function prepareAmount( $amount, $deposit, $number_of_persons )
    {
        return Lib\Utils\Common::calcDeposit( $amount, $deposit, $number_of_persons );
    }

}