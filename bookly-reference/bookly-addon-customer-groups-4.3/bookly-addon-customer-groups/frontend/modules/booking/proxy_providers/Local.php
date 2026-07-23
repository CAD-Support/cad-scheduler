<?php
namespace BooklyCustomerGroups\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCustomerGroups\Lib;
use Bookly\Frontend\Modules\Booking\Proxy;

class Local extends Proxy\CustomerGroups
{
    /**
     * Render "Group Discount" row on a Cart step.
     *
     * @param array  $table = ['headers' => [], 'header_position' => [], 'show' => [] ]
     * @param string $layout
     */
    public static function renderCartDiscountRow( array $table, $layout )
    {
        if ( isset( $table['header_position']['price'] ) ) {
            $wp_user_id = get_current_user_id();
            $customer = new BooklyLib\Entities\Customer();
            if ( $wp_user_id > 0 ) {
                // Try to find customer by WP user ID.
                $customer->loadBy( compact( 'wp_user_id' ) );
            }
            $group = Lib\ProxyProviders\Local::getGroup( $customer->isLoaded() ? $customer->getGroupId() : 0 );
            $discount = $group->getDiscount();
            if ( $discount != 0 ) {
                self::renderTemplate( 'cart_discount_row', compact( 'table', 'discount', 'layout' ) );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function allowedGateway( $gateway, $userData )
    {
        $group_id = $userData->getCustomer()->getGroupId();
        $group = $group_id
            ? Lib\Entities\CustomerGroups::find( $userData->getCustomer()->getGroupId() )
            : null;
        if ( $group ) {
            $allowed = $group->getSkipPayment()
                ? array()
                : ( $group->getGateways() ? json_decode( $group->getGateways(), true ) : null );
        } else {
            $settings = get_option( 'bookly_customer_groups_general_settings' );
            $allowed = $settings['skip_payment'] ? array() : $settings['gateways'];
        }

        // $allowed = null is Default
        return $allowed === null || in_array( $gateway, $allowed );
    }

    /**
     * @inheritDoc
     */
    public static function getSkipPayment( $customer )
    {
        $group = Lib\ProxyProviders\Local::getGroup( $customer->getGroupId() );

        return (bool) $group->getSkipPayment();
    }

    /**
     * @inheritDoc
     */
    public static function prepareCartTotalPrice( $total, BooklyLib\UserBookingData $userData )
    {
        $customer = $userData->getCustomer();
        $group = Lib\ProxyProviders\Local::getGroup( $customer->isLoaded() ? $customer->getGroupId() : 0 );
        $discount = $group->getDiscount();
        if ( substr( $discount, -1 ) === '%' ) {
            $discount = (float) rtrim( $discount, '%' );
            $total = round( $total * ( 100 - $discount ) / 100, 2 );
        } else {
            $total -= (float)$discount;
        }

        return max( $total, 0 );
    }
}