<?php
namespace BooklyCustomerCabinet\Frontend\Components\Dialogs\Reschedule;

use Bookly\Lib;

class Dialog extends Lib\Base\Component
{
    public static function render()
    {
        static::renderTemplate( 'reschedule' );
    }
}