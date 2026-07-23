<?php
namespace BooklyDiscounts\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyDiscounts\Lib;
use BooklyDiscounts\Backend\Modules\Discounts\Page;

class Local extends BooklyLib\Proxy\Discounts
{
    /**
     * @inheritDoc
     */
    public static function addBooklyMenuItem()
    {
        add_submenu_page(
            'bookly-menu',
            __( 'Discounts', 'bookly' ),
            __( 'Discounts', 'bookly' ),
            BooklyLib\Utils\Common::getRequiredCapability(),
            Page::pageSlug(),
            function () { Page::render(); }
        );
    }

    /**
     * @inheritDoc
     */
    public static function prepareServicePrice( $price, $service_id, $nop )
    {
        foreach ( Lib\Utils\Common::getServiceDiscounts( $service_id, $nop ) as $discount ) {
            $price = BooklyLib\Utils\Price::correction( $price, $discount->getDiscount(), $discount->getDeduction() );
        }

        return $price;
    }
}