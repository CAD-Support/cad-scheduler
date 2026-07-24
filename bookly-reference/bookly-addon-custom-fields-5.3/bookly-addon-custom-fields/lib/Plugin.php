<?php
namespace BooklyCustomFields\Lib;

use Bookly\Lib as BooklyLib;
use BooklyCustomFields\Backend;
use BooklyCustomFields\Frontend;

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
        Backend\Components\Dialogs\Appointment\CustomerDetails\ProxyProviders\Shared::init();
        Backend\Modules\Appearance\ProxyProviders\Local::init();
        Backend\Modules\Appearance\ProxyProviders\Shared::init();
        Backend\Modules\Calendar\ProxyProviders\Shared::init();
        Backend\Modules\Notifications\ProxyProviders\Shared::init();
        Backend\Modules\Settings\ProxyProviders\Shared::init();
        Frontend\Modules\Booking\ProxyProviders\Shared::init();
        Frontend\Modules\CustomerProfile\ProxyProviders\Local::init();
        Frontend\Modules\ModernBookingForm\ProxyProviders\Shared::init();

        Notifications\Assets\Item\ProxyProviders\Shared::init();
        ProxyProviders\Local::init();
        ProxyProviders\Shared::init();
    }

    /**
     * @inerhitDoc
     */
    protected static function registerAjax()
    {
        Backend\Modules\CustomFields\Ajax::init();
        Frontend\Modules\Booking\Ajax::init();
    }
}