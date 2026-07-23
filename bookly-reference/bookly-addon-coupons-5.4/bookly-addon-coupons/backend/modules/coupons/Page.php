<?php
namespace BooklyCoupons\Backend\Modules\Coupons;

use Bookly\Lib as BooklyLib;

class Page extends BooklyLib\Base\Component
{
    /**
     * Render page.
     */
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals' ),
        ) );

        self::enqueueScripts( array(
            'module' => array(
                'js/coupons.js' => array( 'bookly-backend-globals' ),
            ),
        ) );

        $services = BooklyLib\Entities\Service::query()
            ->select( 'id, title' )
            ->indexBy( 'id' )
            ->fetchArray();
        $staff_members = BooklyLib\Entities\Staff::query()
            ->select( 'id, full_name AS title' )
            ->indexBy( 'id' )
            ->whereNot( 'visibility', 'archive' )
            ->fetchArray();
        $customers_count = BooklyLib\Entities\Customer::query()->count();
        if ( $customers_count < BooklyLib\Entities\Customer::REMOTE_LIMIT ) {
            $remote = false;
            $customers = BooklyLib\Entities\Customer::query()
                ->select( 'id, full_name, phone, email' )->sortBy( 'full_name' )->fetchArray();
        } else {
            $customers = array();
            $remote = true;
        }

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::COUPONS );

        wp_localize_script( 'bookly-coupons.js', 'BooklyCouponL10n', array(
            'edit' => __( 'Edit', 'bookly' ),
            'duplicate' => __( 'Duplicate', 'bookly' ) . '…',
            'zeroRecords' => __( 'No coupons found.', 'bookly' ),
            'emptyTable' => __( 'No data available in table', 'bookly' ),
            'processing' => __( 'Processing', 'bookly' ) . '…',
            'loadingRecords' => __( 'Loading...', 'bookly' ),
            'areYouSure' => __( 'Are you sure?', 'bookly' ),
            'noResultFound' => __( 'No results found', 'bookly' ),
            'searching' => __( 'Searching', 'bookly' ),
            'remove' => __( 'Remove', 'bookly' ),
            'removeCustomer' => __( 'Remove customer', 'bookly' ),
            'services' => array(
                'allSelected' => __( 'All services', 'bookly' ),
                'nothingSelected' => __( 'No service selected', 'bookly' ),
                'collection' => $services,
                'count' => count( $services ),
            ),
            'staff' => array(
                'allSelected' => __( 'All staff', 'bookly' ),
                'nothingSelected' => __( 'No staff selected', 'bookly' ),
                'collection' => $staff_members,
                'count' => count( $staff_members ),
            ),
            'customers' => array(
                'allSelected' => __( 'All customers', 'bookly' ),
                'nothingSelected' => __( 'No limit', 'bookly' ),
                'collection' => $customers,
                'count' => $customers_count,
                'remote' => $remote,
            ),
            'defaultCodeMask' => get_option( 'bookly_coupons_default_code_mask' ),
            'new_coupon' => __( 'Add Coupon', 'bookly' ) . '…',
            'new_series' => __( 'Add Coupon Series', 'bookly' ) . '…',
            'export' => __( 'Export to CSV', 'bookly' ) . '…',
            'delete' => __( 'Delete', 'bookly' ) . '…',
            'filters' => array(
                'code' => __( 'Coupon code', 'bookly' ),
                'service' => __( 'Service', 'bookly' ),
                'staff' => __( 'Staff', 'bookly' ),
                'customer' => __( 'Customer', 'bookly' ),
                'activeOnly' => __( 'Show only active', 'bookly' ),
                'searchPlaceholder' => __( 'Search', 'bookly' ) . '…',
            ),
            'datatables' => $datatables,
        ) );

        $dropdown_data = array(
            'service' => BooklyLib\Utils\Common::getServiceDataForDropDown(),
            'staff' => BooklyLib\Proxy\Pro::getStaffDataForDropDown(),
        );

        self::renderTemplate( 'index', compact( 'services', 'staff_members', 'customers', 'remote', 'dropdown_data', 'datatables' ) );
    }
}