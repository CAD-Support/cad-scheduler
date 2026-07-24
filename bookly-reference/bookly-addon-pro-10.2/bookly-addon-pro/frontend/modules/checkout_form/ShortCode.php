<?php
namespace BooklyPro\Frontend\Modules\CheckoutForm;

use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\PaymentFlow;
use BooklyPro\Lib;
use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyPro\Backend\Modules\Appearance;

class ShortCode extends BooklyLib\Base\ShortCode
{
    public static $code = 'bookly-checkout-form';

    /**
     * Link styles.
     */
    public static function linkStyles()
    {
        self::enqueueStyles( array(
            'bookly' => array(
                'frontend/resources/css/bootstrap-icons.min.css' => array(),
                'backend/resources/tailwind/tailwind.css' => array(),
            ),
        ) );
    }

    /**
     * Link scripts.
     */
    public static function linkScripts()
    {
        self::enqueueScripts( array(
            'backend' => array(
                'js/common.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/checkout.js' => array( 'bookly-frontend-globals' ),
                'js/checkout-form.js' => array( 'bookly-checkout.js' ),
            ),
        ) );
        $payment_gateways = array();
        foreach ( PaymentFlow::getSupportedGateways() as $type ) {
            if ( ! in_array( $type, array( 'local', 'woocommerce' ) ) ) {
                $payment_gateways[] = array( 'type' => $type, 'title' => BooklyLib\Entities\Payment::typeToString( $type ), 'image' => BooklyLib\Entities\Payment::typeToImage( $type ), 'discount' => -(float) get_option( 'bookly_' . $type . '_increase' ), 'deduction' => -(float) get_option( 'bookly_' . $type . '_addition' ) );
            }
        }
        /** @var BooklyLib\Entities\Payment $payment */
        $payment = BooklyLib\Entities\Payment::query()
            ->where( 'token', self::parameter( 'payment_token' ) )
            ->findOne();

        $payment_data = null;
        if ( $payment ) {
            $payment_data = array(
                'id' => $payment->getId(),
                'total' => $payment->getTotal(),
                'paid' => $payment->getPaid(),
                'child_paid' => $payment->getChildPaid(),
                'status' => $payment->getStatus(),
                'amount' => $payment->getTotal() - $payment->getPaid() - $payment->getChildPaid(),
            );
        }

        wp_localize_script( 'bookly-checkout-form.js', 'BooklyL10nCheckoutForm', array(
            'format_price' => BooklyLib\Utils\Price::formatOptions(),
            'gateways' => $payment_gateways,
            'payment_data' => $payment_data,
            'payment_token' => self::parameter( 'payment_token' ),
        ) );
    }

    /**
     * Render shortcode.
     *
     * @param array $attr
     * @return string
     */
    public static function render( $attr )
    {
        global $sitepress;

        // Disable caching.
        BooklyLib\Utils\Common::noCache();

        // Prepare URL for AJAX requests.
        $ajaxurl = admin_url( 'admin-ajax.php' );

        // Support WPML.
        if ( $sitepress instanceof \SitePress ) {
            $ajaxurl = add_query_arg( array( 'lang' => $sitepress->get_current_language() ), $ajaxurl );
        }

        $appearance = Appearance\ProxyProviders\Local::getAppearance( Lib\Entities\Form::TYPE_CHECKOUT_FORM, is_array( $attr ) ? current( $attr ) : null );
        if ( isset( $appearance['token'] ) ) {
            $form_type = 'bookly-checkout-form-' . $appearance['token'];
        } else {
            $form_type = 'bookly-checkout-form';
        }

        $form_id = uniqid( $form_type . '-', false );

        return self::renderTemplate( 'short_code', compact( 'ajaxurl', 'form_id', 'form_type', 'appearance' ), false );
    }
}