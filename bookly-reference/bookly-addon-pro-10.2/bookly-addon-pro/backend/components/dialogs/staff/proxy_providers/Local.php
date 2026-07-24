<?php
namespace BooklyPro\Backend\Components\Dialogs\Staff\ProxyProviders;

use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Dialogs\Staff\Proxy;
use Bookly\Lib as BooklyLib;

class Local extends Proxy\Pro
{
    /**
     * @inheritDoc
     */
    public static function renderCategoriesDialog()
    {
        self::enqueueStyles( array(
            'plugin' => array(
                'backend/components/dialogs/staff/categories/resources/css/fontawesome-all.min.css' => array( 'bookly-backend-globals' ),
            ),
        ) );

        self::enqueueScripts( array(
            'plugin' => array(
                'backend/components/dialogs/staff/categories/resources/js/staff-categories-dialog.js' => array( 'bookly-backend-globals', ),
            ),
        ) );

        \BooklyPro\Backend\Components\Dialogs\Staff\Categories\Ajax::renderTemplate( 'dialog' );
    }

    /**
     * @inheritDoc
     */
    public static function renderDuplicateDialog()
    {
        self::enqueueScripts( array(
            'plugin' => array(
                'backend/components/dialogs/staff/duplicate/resources/js/duplicate-staff-dialog.js' => array( 'bookly-backend-globals', )
            ),
        ) );

        wp_localize_script( 'bookly-duplicate-staff-dialog.js', 'BooklyL10nDuplicateStaffDialog', array(
            'l10n' => array(
                'duplicate_staff' => __( 'Duplicate staff member', 'bookly' ),
                'staff_member_to_duplicate' => __( 'Staff member to duplicate', 'bookly' ),
                'duplicate' => __( 'Duplicate', 'bookly' ),
                'info' => __( 'You\'re about to duplicate this staff member. All settings - except phone number, email, and calendar settings - will be copied. The new staff member\'s visibility will be set to \'Private\' by default.', 'bookly' ),
                'new_staff_name' => __( 'Name of duplicated staff member', 'bookly' ),
                'copy_of' => __( 'Copy of', 'bookly' ),
                'close' => __( 'Close', 'bookly' ),
            ),
        ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderAdd()
    {
        print '<div class="col-12 col-sm-auto">';
        Buttons::renderDefault( null, 'w-100 mb-3', __( 'Categories', 'bookly' ), array( 'data-toggle' => 'bookly-modal', 'data-target' => '#bookly-staff-categories-modal', 'disabled' => 'disabled' ), true );
        print '</div>';

        print '<div class="col-12 col-sm-auto">';
        Buttons::renderDefault( 'bookly-duplicate-staff-dialog-show', 'w-100 mb-3', __( 'Duplicate', 'bookly' ), array(), true );
        print '</div>';
    }
}