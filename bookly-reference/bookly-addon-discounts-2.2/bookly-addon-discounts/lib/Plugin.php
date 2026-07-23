<?php
namespace BooklyDiscounts\Lib;

use Bookly\Lib as BooklyLib;
use BooklyDiscounts\Backend\Components;
use BooklyDiscounts\Frontend;
use BooklyDiscounts\Backend;

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
        Frontend\Modules\Booking\ProxyProviders\Local::init();
        Frontend\Modules\ModernBookingForm\ProxyProviders\Shared::init();

        ProxyProviders\Local::init();
        ProxyProviders\Shared::init();
    }

    /**
     * @inheritDoc
     */
    protected static function registerAjax()
    {
        Components\Dialogs\Discount\EditAjax::init();
        Backend\Modules\Discounts\Ajax::init();
    }
}