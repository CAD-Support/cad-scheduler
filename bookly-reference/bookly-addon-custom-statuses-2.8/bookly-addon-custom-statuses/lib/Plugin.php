<?php
namespace BooklyCustomStatuses\Lib;

use Bookly\Lib as BooklyLib;
use BooklyCustomStatuses\Backend;

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
        Backend\Modules\Settings\ProxyProviders\Local::init();
        Backend\Modules\Settings\ProxyProviders\Shared::init();
        ProxyProviders\Local::init();
        ProxyProviders\Shared::init();
    }

    /**
     * @inerhitDoc
     */
    protected static function registerAjax()
    {
        Backend\Modules\Settings\Ajax::init();
    }
}