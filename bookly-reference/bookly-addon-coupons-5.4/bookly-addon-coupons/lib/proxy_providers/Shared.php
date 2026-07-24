<?php
namespace BooklyCoupons\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCoupons\Lib\Entities\Coupon;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * Add the Coupons tab to the Settings submenu in the fullscreen header sidebar.
     *
     * @param array $submenus
     * @return array
     */
    public static function buildHeaderSubmenus( $submenus )
    {
        if ( isset( $submenus['bookly-settings'] ) ) {
            $submenus['bookly-settings'][] = array( 'label' => __( 'Coupons', 'bookly' ), 'tab' => 'coupons', 'badge' => '' );
        }

        return $submenus;
    }

    /**
     * @inheritDoc
     */
    public static function preparePaymentDetails( BooklyLib\DataHolders\Details\Payment $details )
    {
        $coupon = $details->getCartInfo()->getCoupon();
        if ( $coupon ) {
            $details->setData( array(
                'coupon' => array(
                    'code' => $coupon->getCode(),
                    'discount' => $coupon->getDiscount(),
                    'deduction' => $coupon->getDeduction(),
                ),
                'coupon_id' => $coupon->getId(),
            ) );
            $details->getPayment()->setCouponId( $coupon->getId() );
        }

        return $details;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCustomerAppointmentCodes( $codes, $customer_appointment, $format )
    {
        $payment = $customer_appointment->getPaymentId()
            ? BooklyLib\Entities\Payment::find( $customer_appointment->getPaymentId() )
            : null;

        $codes['coupon'] = $payment
            ? Coupon::query()->where( 'id', $payment->getCouponId() )->fetchVar( 'code' )
            : '';

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableColumns( $columns, $table )
    {
        if ( $table === BooklyLib\Utils\Tables::COUPONS ) {
            $columns = array_merge( $columns, array(
                'id' => esc_html__( 'ID', 'bookly' ),
                'code' => esc_html__( 'Code', 'bookly' ),
                'discount' => esc_html__( 'Discount (%)', 'bookly' ),
                'deduction' => esc_html__( 'Deduction', 'bookly' ),
                'services' => esc_html__( 'Services', 'bookly' ),
                'staff' => esc_html__( 'Staff', 'bookly' ),
                'customers' => esc_html__( 'Customers limit', 'bookly' ),
                'usage_limit' => esc_html__( 'Usage limit', 'bookly' ),
                'used' => esc_html__( 'Number of times used', 'bookly' ),
                'date_limit_start' => esc_html__( 'Active from', 'bookly' ),
                'date_limit_end' => esc_html__( 'Active until', 'bookly' ),
                'min_appointments' => esc_html__( 'Min. appointments', 'bookly' ),
                'max_appointments' => esc_html__( 'Max. appointments', 'bookly' ),
            ) );
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableDefaultSettings( $columns, $table )
    {
        if ( $table === BooklyLib\Utils\Tables::COUPONS ) {
            $columns = array_merge( $columns, array(
                'id' => false,
            ) );
        }

        return $columns;
    }
}