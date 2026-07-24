<?php
namespace BooklyCompoundServices\Backend\Modules\Calendar\ProxyProviders;

use Bookly\Backend\Modules\Calendar\Proxy;
use BooklyCompoundServices\Backend\Components;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function renderAddOnsComponents()
    {
        Components\Dialogs\Appointment\Dialog::render();
    }
}