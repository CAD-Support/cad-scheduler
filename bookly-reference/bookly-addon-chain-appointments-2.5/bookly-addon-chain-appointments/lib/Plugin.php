<?php
namespace BooklyChainAppointments\Lib;

use Bookly\Lib as BooklyLib;
use BooklyChainAppointments\Backend;
use BooklyChainAppointments\Frontend;

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
        // Init proxy.
        Backend\Modules\Appearance\ProxyProviders\Local::init();
        Backend\Modules\Appearance\ProxyProviders\Shared::init();
        if ( get_option( 'bookly_chain_appointments_enabled' ) ) {
            Frontend\Modules\Booking\ProxyProviders\Shared::init();
        }
    }
}