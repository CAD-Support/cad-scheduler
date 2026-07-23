<?php
namespace BooklyCoupons\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCoupons\Lib;
use BooklyCoupons\Backend\Modules\Coupons\Page;

class Local extends BooklyLib\Proxy\Coupons
{
    /**
     * @inheritDoc
     */
    public static function addBooklyMenuItem()
    {
        add_submenu_page(
            'bookly-menu',
            __( 'Coupons', 'bookly' ),
            __( 'Coupons', 'bookly' ),
            BooklyLib\Utils\Common::getRequiredCapability(),
            Page::pageSlug(),
            function () { Page::render(); }
        );
    }

}