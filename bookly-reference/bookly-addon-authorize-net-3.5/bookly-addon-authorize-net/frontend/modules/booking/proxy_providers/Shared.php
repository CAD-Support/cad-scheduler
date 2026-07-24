<?php
namespace BooklyAuthorizeNet\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function preparePaymentOptions( $options, $form_id, $show_price, BooklyLib\CartInfo $cart_info, $userData )
    {
        $gateway = BooklyLib\Entities\Payment::TYPE_AUTHORIZENET;
        if ( Proxy\CustomerGroups::allowedGateway( $gateway, $userData ) !== false ) {
            $url_cards_image = plugins_url( 'frontend/resources/images/payments.svg', BooklyLib\Plugin::getMainFile() );
            $cart_info->setGateway( $gateway );

            $options[ $gateway ] = array(
                'html' => self::renderTemplate(
                    'payment_option',
                    compact( 'form_id', 'url_cards_image', 'show_price', 'cart_info' ),
                    false
                ),
                'pay' => $cart_info->getPayNow(),
            );
        }

        return $options;
    }
}