<?php
namespace BooklyCustomerInformation\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_customer_information_data' => '[]',
        );
    }
}