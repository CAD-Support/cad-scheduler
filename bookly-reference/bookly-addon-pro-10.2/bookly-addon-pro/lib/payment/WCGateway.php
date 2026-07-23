<?php
namespace BooklyPro\Lib\Payment;

use Bookly\Lib as BooklyLib;

class WCGateway extends BooklyLib\Base\Gateway
{
    protected $type = BooklyLib\Entities\Payment::TYPE_WOOCOMMERCE;

    public function retrieveStatus()
    {
    }

    protected function createGatewayIntent()
    {
    }

    protected function getCheckoutUrl( array $intent_data )
    {
    }

    protected function getInternalMetaData()
    {
    }
}