<?php
namespace BooklyPro\Backend\Components\Dialogs\Payment\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Payment\Proxy;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inerhitDoc
     */
    public static function preparePaymentInfo( $payment_info, $total )
    {
        if ( Lib\Config::needCreateWCOrder( $total ) ) {
            $payment_info['payment_title'] = BooklyLib\Entities\Payment::paymentInfo( $total, $total, BooklyLib\Entities\Payment::TYPE_WOOCOMMERCE, BooklyLib\Entities\Payment::STATUS_COMPLETED );
            $payment_info['payment_type'] = 'full';
        }

        return $payment_info;
    }

    /**
     * @inerhitDoc
     */
    public static function preparePaymentDetails( $data, $payment )
    {
        $checkout_urls = array();
        /** @var Lib\Entities\Form $form */
        foreach ( Lib\Entities\Form::query()->where( 'type', 'checkout-form' )->find() as $form ) {
            $settings = json_decode( $form->getSettings(), true );
            if ( isset( $settings['page_url'] ) && $settings['page_url'] ) {
                $checkout_urls[] = array(
                    'name' => $form->getName(),
                    'url' => add_query_arg( 'payment_token', $payment->getToken(), $settings['page_url'] ),
                );
            }
        }
        $data['checkout_urls'] = $checkout_urls;

        return $data;
    }
}