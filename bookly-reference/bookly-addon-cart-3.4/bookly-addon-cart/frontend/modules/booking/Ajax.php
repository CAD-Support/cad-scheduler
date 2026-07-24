<?php
namespace BooklyCart\Frontend\Modules\Booking;

use Bookly\Frontend\Modules\Booking\Lib\Errors;
use Bookly\Lib as BooklyLib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Drop cart item.
     */
    public static function dropItem()
    {
        $userData = new BooklyLib\UserBookingData( self::parameter( 'form_id' ) );
        if ( $userData->load() ) {
            $cart_key = self::parameter( 'cart_key' );
            $edit_cart_keys = $userData->getEditCartKeys();

            $userData->cart->drop( $cart_key );
            if ( ( $idx = array_search( $cart_key, $edit_cart_keys ) ) !== false ) {
                unset ( $edit_cart_keys[ $idx ] );
                $userData->setEditCartKeys( $edit_cart_keys );
            }

            $cart_info = $userData->cart->getInfo();
            $userData->sessionSave();

            wp_send_json_success(
                array(
                    'subtotal_price' => BooklyLib\Utils\Price::format( $cart_info->getSubtotal() ),
                    'subtotal_deposit' => BooklyLib\Utils\Price::format( $cart_info->getDeposit() ),
                    'pay_now_deposit' => BooklyLib\Utils\Price::format( $cart_info->getPayNow() ),
                    'pay_now_tax' => BooklyLib\Utils\Price::format( $cart_info->getPayTax() ),
                    'total_price' => BooklyLib\Utils\Price::format( $cart_info->getTotal() ),
                    'total_tax' => BooklyLib\Utils\Price::format( $cart_info->getTotalTax() ),
                    'waiting_list_price' => BooklyLib\Utils\Price::format( -$cart_info->getWaitingListTotal() ),
                    'waiting_list_deposit' => BooklyLib\Utils\Price::format( -$cart_info->getWaitingListDeposit() ),
                    'total_waiting_list' => $cart_info->getWaitingListTotal() > 0 ? BooklyLib\Utils\Price::format( - $cart_info->getWaitingListTotal() ) : null,
                )
            );
        }

        Errors::sendSessionError();
    }
}