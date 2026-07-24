<?php
namespace BooklyCustomerGroups\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCustomerGroups\Lib\Entities;
use BooklyCustomerGroups\Lib;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareCaSeStQuery( BooklyLib\Query $query )
    {
        $user_id  = get_current_user_id();
        $customer = new BooklyLib\Entities\Customer();
        if ( $user_id > 0 ) {
            // Try to find customer by WP user ID.
            $customer->loadBy( array( 'wp_user_id' => $user_id ) );
        }
        if ( $customer->isLoaded() ) {
            $query->whereRaw(
                's.visibility = %s OR s.visibility = %s AND s.id IN (SELECT cgs.service_id FROM `' . Entities\CustomerGroupsServices::getTableName() . '` cgs WHERE cgs.group_id = %d)',
                array(
                    BooklyLib\Entities\Service::VISIBILITY_PUBLIC,
                    BooklyLib\Entities\Service::VISIBILITY_GROUP_BASED,
                    $customer->getGroupId(),
                )
            );
        } else {
            $query->where( 's.visibility', BooklyLib\Entities\Service::VISIBILITY_PUBLIC );
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    public static function preparePaymentDetails( $details )
    {
        if ( $details->getCartInfo()->getGroupDiscount() != 0 ) {
            $customer = $details->getCustomer();
            $group = Lib\ProxyProviders\Local::getGroup( $customer->getGroupId() );
            $details->setData( array(
                'customer_group' => array(
                    'discount_format' => strpos( $group->getDiscount(), '%' ) === false ? BooklyLib\Utils\Price::format( $group->getDiscount() ) : $group->getDiscount(),
                ),
            ) );
        }

        return $details;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableColumns( $columns, $table )
    {
        switch ( $table ) {
            case BooklyLib\Utils\Tables::CUSTOMER_GROUPS:
                $columns = array_merge( $columns, array(
                    'id' => esc_html__( 'ID', 'bookly' ),
                    'name' => esc_html__( 'Group name', 'bookly' ),
                    'customers_count' => esc_html__( 'Number of users', 'bookly' ),
                    'description' => esc_html__( 'Description', 'bookly' ),
                    'appointment_status' => esc_html__( 'Appointment status', 'bookly' ),
                    'discount' => esc_html__( 'Discount', 'bookly' ),
                    'gateways' => esc_html__( 'Payment methods', 'bookly' ),
                ) );
                break;

            case BooklyLib\Utils\Tables::CUSTOMERS:
                $columns['group_name'] = esc_html__( 'Group', 'bookly' );
                break;
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableDefaultSettings( $columns, $table )
    {
        if ( $table === BooklyLib\Utils\Tables::CUSTOMER_GROUPS ) {
            $columns = array_merge( $columns, array(
                'id' => false,
                'gateways' => false,
            ) );
        }

        return $columns;
    }
}