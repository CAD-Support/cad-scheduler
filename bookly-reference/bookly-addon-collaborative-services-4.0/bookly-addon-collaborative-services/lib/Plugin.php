<?php
namespace BooklyCollaborativeServices\Lib;

use Bookly\Lib as BooklyLib;
use BooklyCollaborativeServices\Backend;
use BooklyCollaborativeServices\Frontend;

abstract class Plugin extends BooklyLib\Base\Plugin
{
    protected static $prefix;
    protected static $title;
    protected static $version;
    protected static $slug;
    protected static $directory;
    protected static $main_file;
    protected static $basename;
    protected static $text_domain;
    protected static $root_namespace;
    protected static $embedded;

    /**
     * @inheritDoc
     */
    protected static function init()
    {
        // Register proxy methods.
        Frontend\Modules\ModernBookingForm\ProxyProviders\Shared::init();
        Backend\Components\Dialogs\Appointment\ProxyProviders\Shared::init();
        Backend\Components\Dialogs\Service\Edit\ProxyProviders\Local::init();
        Backend\Components\Dialogs\Service\Edit\ProxyProviders\Shared::init();
        Backend\Modules\Appointments\ProxyProviders\Shared::init();
        Backend\Modules\Calendar\ProxyProviders\Shared::init();
        Backend\Modules\Services\ProxyProviders\Shared::init();
        Backend\Modules\Settings\ProxyProviders\Shared::init();
        ProxyProviders\Shared::init();
    }

    /**
     * @inheritDoc
     */
    protected static function registerAjax()
    {
        Backend\Components\Dialogs\Appointment\Ajax::init();
    }
}