<?php
namespace BooklyPro\Lib\Notifications\Cart\ProxyProviders;

use Bookly\Lib\DataHolders\Booking\Order;
use Bookly\Lib\Notifications\Cart\Proxy;
use BooklyPro\Lib\Notifications\Cart\Sender;

abstract class Local extends Proxy\Pro
{
    /**
     * @inheritDoc
     */
    public static function sendCombinedToClient( Order $order, $queue = null )
    {
        Sender::sendCombined( $order, $queue );

        return $queue;
    }
}