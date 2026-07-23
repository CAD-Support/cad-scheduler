<?php
namespace BooklyDepositPayments\Lib\Utils;

use Bookly\Lib\Utils\Price;

abstract class Common
{
    /**
     * Calculate deposit.
     *
     * @param double $deposit_amount
     * @param string $deposit
     * @param int    $number_of_persons
     * @return double
     */
    public static function calcDeposit( $deposit_amount, $deposit, $number_of_persons )
    {
        if ( $deposit === '' ) {
            return $deposit_amount * $number_of_persons;
        }
        if ( substr( $deposit, -1 ) === '%' ) {
            $deposit = (float) rtrim( $deposit, '%' );
            $deposit = $deposit_amount * $deposit / 100;
        } else {
            $deposit = (float) $deposit * $number_of_persons;
        }

        return round( min( $deposit, $deposit_amount ), 2 );
    }

    /**
     * Format deposit.
     *
     * @param double $deposit_amount
     * @param string $deposit
     * @return string
     */
    public static function formatDeposit( $deposit_amount, $deposit )
    {
        $result = Price::format( $deposit_amount );
        if ( strpos( $deposit, '%' ) !== false ) {
            $result .= ' (' . $deposit . ')';
        }

        return $result;
    }

}