<?php
namespace BooklyCompoundServices\Backend\Components\Dialogs\Appointment;

use Bookly\Lib as BooklyLib;

class Dialog extends BooklyLib\Base\Component
{
    /**
     * Render compound dialog.
     */
    public static function render()
    {
        self::enqueueScripts( array(
            'module' => array( 'js/compound-dialog.js' => array( 'bookly-backend-globals' ), ),
        ) );

        wp_localize_script( 'bookly-compound-dialog.js', 'BooklyL10nCompoundDialog', array(
            'csrf_token' => BooklyLib\Utils\Common::getCsrfToken(),
            'moment_format_date' => BooklyLib\Utils\DateTime::convertFormat( 'date', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'moment_format_time' => BooklyLib\Utils\DateTime::convertFormat( 'time', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'l10n' => array(
                'compound_service' => __( 'Compound service', 'bookly' ),
                'close' => __( 'Close', 'bookly' ),
                'edit' => __( 'Edit', 'bookly' ),
                'staff_any' => get_option( 'bookly_l10n_option_employee' ),
            ),
        ) );
    }
}