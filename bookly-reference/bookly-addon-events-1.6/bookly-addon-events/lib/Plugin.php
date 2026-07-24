<?php
namespace BooklyEvents\Lib;

use Bookly\Lib\Base;
use BooklyEvents\Backend;
use BooklyEvents\Frontend;

abstract class Plugin extends Base\Plugin
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
        Backend\Modules\Appearance\ProxyProviders\Shared::init();
        Backend\Modules\Appointments\ProxyProviders\Shared::init();
        Backend\Modules\Calendar\ProxyProviders\Local::init();
        Backend\Modules\Notifications\ProxyProviders\Shared::init();
        Backend\Modules\Settings\ProxyProviders\Shared::init();
        Payment\ProxyProviders\Local::init();
        Payment\ProxyProviders\Shared::init();
        ProxyProviders\Local::init();
        ProxyProviders\Shared::init();
        Entities\ProxyProviders\Shared::init();
        Frontend\Modules\ModernBookingForm\ProxyProviders\Shared::init();
        Frontend\Modules\Booking\ProxyProviders\Local::init();

        if ( ! is_admin() ) {
            // Init short code.
            Frontend\Modules\EventsForm\ShortCode::init();
        }
    }

    /**
     * @inerhitDoc
     */
    protected static function registerAjax()
    {
        Frontend\Modules\EventsForm\Ajax::init();
        Backend\Modules\Events\Ajax::init();
        Backend\Components\Dialogs\Event\Ajax::init();
        Backend\Components\Dialogs\TicketType\Ajax::init();
        Backend\Components\Dialogs\Attendees\Ajax::init();
    }
}