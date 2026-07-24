<?php
namespace BooklyCustomerCabinet\Frontend\Components\Dialogs\Delete;

use Bookly\Lib;

class Dialog extends Lib\Base\Component
{
    public static function render()
    {
        static::renderTemplate( 'delete' );
    }
}