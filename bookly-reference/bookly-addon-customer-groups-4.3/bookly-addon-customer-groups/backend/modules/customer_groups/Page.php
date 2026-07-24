<?php
namespace BooklyCustomerGroups\Backend\Modules\CustomerGroups;

use Bookly\Lib as BooklyLib;

class Page extends BooklyLib\Base\Component
{
    /**
     * Render page.
     */
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals', ),
        ) );

        self::enqueueScripts( array(
            'module' => array(
                'js/customer-groups.js' => array( 'bookly-backend-globals' ),
            ),
        ) );

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::CUSTOMER_GROUPS );

        wp_localize_script( 'bookly-customer-groups.js', 'BooklyCustomerGroupsL10n', array(
            'new_group' => __( 'New group', 'bookly' ) . '…',
            'edit_group' => __( 'Edit group', 'bookly' ),
            'general_settings' => __( 'General settings', 'bookly' ) . '…',
            'delete' => __( 'Delete', 'bookly' ) . '…',
            'search' => __( 'Quick search by group name', 'bookly' ) . '…',
            'are_you_sure' => __( 'Are you sure?', 'bookly' ),
            'zeroRecords' => __( 'No customer groups yet.', 'bookly' ),
            'edit' => __( 'Edit', 'bookly' ),
            'no_result_found' => __( 'No results found', 'bookly' ),
            'all_selected' => __( 'All methods', 'bookly' ),
            'nothing_selected' => __( 'No methods selected', 'bookly' ),
            'default' => __( 'Default', 'bookly' ),
            'gateways' => BooklyLib\Utils\Common::getGateways(),
            'datatables' => $datatables,
        ) );

        $no_groups_count = BooklyLib\Entities\Customer::query( 'c' )
            ->select( 'c.id' )
            ->where( 'c.group_id', null )
            ->count();

        self::renderTemplate( 'index', compact( 'no_groups_count', 'datatables' ) );
    }
}