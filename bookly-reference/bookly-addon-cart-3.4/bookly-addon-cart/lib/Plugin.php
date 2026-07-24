<?php
namespace BooklyCart\Lib;

use Bookly\Lib;
use BooklyCart\Backend;
use BooklyCart\Frontend;

abstract class Plugin extends Lib\Base\Plugin
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
        Frontend\Modules\ModernBookingForm\ProxyProviders\Shared::init();
        if ( get_option( 'bookly_cart_enabled' ) ) {
            Frontend\Modules\Booking\ProxyProviders\Local::init();
        }
    }

    /**
     * @inheritDoc
     */
    protected static function registerAjax()
    {
        if ( get_option( 'bookly_cart_enabled' ) ) {
            Frontend\Modules\Booking\Ajax::init();
        }
    }
}