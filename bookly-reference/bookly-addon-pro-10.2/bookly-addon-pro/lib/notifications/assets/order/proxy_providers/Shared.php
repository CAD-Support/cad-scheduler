<?php
namespace BooklyPro\Lib\Notifications\Assets\Order\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Notifications\Assets\Order\Codes;
use Bookly\Lib\Notifications\Assets\Order\Proxy;
use BooklyPro\Lib\Entities;

abstract class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCodes( Codes $codes )
    {
        $customer = $codes->getOrder()->getCustomer();
        if ( $customer && $customer->getBirthday() ) {
            $codes->client_birthday = date_i18n( 'F j', strtotime( $customer->getBirthday() ) );
            $codes->client_full_birthday = BooklyLib\Utils\DateTime::formatDate( $customer->getBirthday() );
        } else {
            $codes->client_birthday = '';
            $codes->client_full_birthday = '';
        }
        if ( $codes->getOrder()->hasPayment() ) {
            /** @var Entities\Form $form */
            foreach ( Entities\Form::query()->where( 'type', 'checkout-form' )->find() as $form ) {
                $settings = json_decode( $form->getSettings(), true );
                if ( isset( $settings['page_url'] ) && $settings['page_url'] ) {
                    $codes->checkout_forms[ 'checkout_form#' . $form->getId() ] = add_query_arg( 'payment_token', $codes->getOrder()->getPayment()->getToken(), $settings['page_url'] );
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function prepareReplaceCodes( array $replace_codes, Codes $codes, $format )
    {
        $replace_codes['client_birthday'] = $codes->client_birthday;
        $replace_codes['client_full_birthday'] = $codes->client_full_birthday;
        foreach ( $codes->checkout_forms as $key => $url ) {
            $replace_codes[ $key ] = $url;
        }

        return $replace_codes;
    }
}