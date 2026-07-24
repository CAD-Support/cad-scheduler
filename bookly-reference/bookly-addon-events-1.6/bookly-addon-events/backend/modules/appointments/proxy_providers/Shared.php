<?php
namespace BooklyEvents\Backend\Modules\Appointments\ProxyProviders;

use Bookly\Backend\Modules\Calendar\Proxy;
use BooklyEvents\Backend\Components;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function renderAddOnsComponents()
    {
        Components\Dialogs\Attendees\Dialog::render();
    }
}