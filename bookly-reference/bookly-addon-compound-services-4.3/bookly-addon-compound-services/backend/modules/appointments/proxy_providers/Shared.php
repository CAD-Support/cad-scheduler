<?php
namespace BooklyCompoundServices\Backend\Modules\Appointments\ProxyProviders;

use Bookly\Backend\Modules\Appointments\Proxy;
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