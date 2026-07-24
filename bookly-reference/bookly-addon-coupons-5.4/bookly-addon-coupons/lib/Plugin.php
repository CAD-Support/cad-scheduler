<?php
namespace BooklyCoupons\Lib;

use BooklyCoupons\Backend;
use BooklyCoupons\Frontend;

abstract class Plugin extends \Bookly\Lib\Base\Plugin
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
        Backend\Modules\Calendar\ProxyProviders\Shared::init();
        Backend\Modules\Customers\ProxyProviders\Shared::init();
        Backend\Modules\Notifications\ProxyProviders\Shared::init();
        Backend\Modules\Settings\ProxyProviders\Shared::init();
        Frontend\Modules\Booking\ProxyProviders\Local::init();
        Frontend\Modules\ModernBookingForm\ProxyProviders\Shared::init();
        ProxyProviders\Local::init();
        ProxyProviders\Shared::init();
        Notifications\Assets\Order\ProxyProviders\Shared::init();
        Notifications\Assets\Test\ProxyProviders\Shared::init();
    }

    /**
     * @inerhitDoc
     */
    protected static function registerAjax()
    {
        Backend\Modules\Coupons\Ajax::init();
        Frontend\Modules\ModernBookingForm\Ajax::init();
        if ( get_option( 'bookly_coupons_enabled' ) ) {
            Frontend\Modules\Booking\Ajax::init();
        }
    }
}