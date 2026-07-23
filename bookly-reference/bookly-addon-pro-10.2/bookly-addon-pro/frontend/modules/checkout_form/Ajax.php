<?php
namespace BooklyPro\Frontend\Modules\CheckoutForm;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    public static function checkoutFormPay()
    {
        try {
            /** @var Entities\Payment $parent_payment */
            $parent_payment = BooklyLib\Entities\Payment::query()
                ->where( 'token', self::parameter( 'payment_token' ) )
                ->findOne();
            $gateway_name = self::parameter( 'gateway' );
            $request = \Bookly\Frontend\Modules\Payment\Request::getInstance();
            $request->setGatewayName( $gateway_name );

            $userData = new BooklyLib\UserBookingData( null, 0 );
            $customer = Entities\Customer::find( $parent_payment->getCustomerId() );

            $amount = $parent_payment->getTotal() - $parent_payment->getPaid() - $parent_payment->getChildPaid();
            $increase = (float) get_option( 'bookly_' . $gateway_name . '_increase' );
            $addition = (float) get_option( 'bookly_' . $gateway_name . '_addition' );
            $price_correction = BooklyLib\Utils\Price::correction( $amount, -$increase, -$addition ) - $amount;

            $parent_details = $parent_payment->getDetailsData();

            $tax_amount = $parent_payment->getTax() - $parent_details->getValue( 'tax_paid' ) ?: 0 - $parent_details->getValue( 'child_tax_paid' ) ?: 0;

            $child_payment = new Entities\Payment();
            $child_payment
                ->setType( $gateway_name )
                ->setStatus( Entities\Payment::STATUS_PENDING )
                ->setTotal( $amount )
                ->setPaid( $amount )
                ->setGatewayPriceCorrection( $price_correction )
                ->setPaidType( Entities\Payment::PAY_IN_FULL )
                ->setTax( $tax_amount )
                ->setParentId( $parent_payment->getId() )
                ->save();

            $cart_item = new BooklyLib\CartItem();
            $cart_item
                ->setType( 'child_payment' )
                ->setPaymentId( $child_payment->getId() );
            $userData->cart->add( $cart_item );
            $userData->cart->getInfo( $gateway_name, false )->setGateway( $gateway_name );
            $userData->setCustomer( $customer );
            $userData
                ->setFirstName( $customer->getFirstName() )
                ->setLastName( $customer->getLastName() )
                ->setFullName( $customer->getFullName() )
                ->setEmail( $customer->getEmail() )
                ->setPhone( $customer->getPhone() );
            $request->setUserData( $userData );

            $gateway = $request->getGateway();

            $gateway->setPayment( $child_payment );
        } catch ( \Exception $e ) {
            wp_send_json_error();
        }

        try {
            wp_send_json_success( $request->getGateway()->createCheckout() );
        } catch ( \Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }
    }

    /**
     * Override the parent method to exclude actions from CSRF token verification.
     *
     * @param string $action
     * @return bool
     */
    protected static function csrfTokenValid( $action = null )
    {
        return $action === 'checkoutFormPay' || parent::csrfTokenValid( $action );
    }
}