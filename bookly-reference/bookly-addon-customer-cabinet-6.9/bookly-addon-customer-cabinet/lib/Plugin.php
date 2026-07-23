<?php
namespace BooklyCustomerCabinet\Lib;

use BooklyCustomerCabinet\Backend;
use BooklyCustomerCabinet\Frontend;
use Bookly\Lib as BooklyLib;

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
        Backend\Components\TinyMce\ProxyProviders\Shared::init();
        Backend\Components\Gutenberg\CustomerCabinet\Block::init();
        if ( ! is_admin() ) {
            Frontend\Modules\CustomerCabinet\ShortCode::init();
        }
    }

    /**
     * @inerhitDoc
     */
    protected static function registerAjax()
    {
        Frontend\Components\Dialogs\Cancel\Ajax::init();
        Frontend\Components\Dialogs\Delete\Ajax::init();
        Frontend\Components\Dialogs\Reschedule\Ajax::init();
        Frontend\Modules\CustomerCabinet\Ajax::init();
    }
}