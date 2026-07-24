<?php
namespace BooklyDepositPayments\Lib;

use Bookly\Lib as BooklyLib;
use BooklyDepositPayments\Backend\Components;
use BooklyDepositPayments\Backend;
use BooklyDepositPayments\Frontend;

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
        Backend\Modules\Appearance\ProxyProviders\Local::init();
        Backend\Modules\Appearance\ProxyProviders\Shared::init();
        Backend\Modules\Notifications\ProxyProviders\Shared::init();
        Backend\Modules\Settings\ProxyProviders\Local::init();
        Backend\Modules\Settings\ProxyProviders\Shared::init();
        Components\Dialogs\Service\Edit\ProxyProviders\Local::init();
        Components\Dialogs\Staff\Edit\ProxyProviders\Shared::init();
        Components\TinyMce\ProxyProviders\Local::init();
        Frontend\Modules\Booking\ProxyProviders\Local::init();
        Frontend\Modules\ModernBookingForm\ProxyProviders\Shared::init();
        Notifications\Assets\Order\ProxyProviders\Shared::init();
        Notifications\Assets\Test\ProxyProviders\Shared::init();
        ProxyProviders\Local::init();
    }

    /**
     * @inheritDoc
     */
    protected static function registerAjax()
    {
        Frontend\Modules\Booking\Ajax::init();
    }
}