<?php
namespace BooklyCustomerGroups\Backend\Components\Dialogs\CustomerGroup;

use Bookly\Lib as BooklyLib;

class Edit extends BooklyLib\Base\Component
{
    /**
     * Render customer groups dialog.
     */
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals', ),
        ) );

        self::enqueueScripts( array(
            'module' => array( 'js/customer-groups-dialog.js' => array( 'bookly-backend-globals' ), ),
        ) );

        wp_localize_script( 'bookly-customer-groups-dialog.js', 'BooklyL10nCustomerGroupsDialog', array(
            'general_settings' => get_option( 'bookly_customer_groups_general_settings' ),
            'l10n' => array(
                'settings' => __( 'Settings for customers without group', 'bookly' ),
                'new_group' => __( 'New group', 'bookly' ),
                'edit_group' => __( 'Edit group', 'bookly' ),
                'name_required_error' => __( 'Group name is required', 'bookly' ),
            ),
        ) );

        $gateways = BooklyLib\Utils\Common::getGateways();

        self::renderTemplate( 'edit', compact( 'gateways' ) );
    }
}