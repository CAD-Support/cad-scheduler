<?php
namespace BooklyPro\Backend\Components\License;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Utils\Common;
use Bookly\Backend\Modules\Shop;
use BooklyPro\Lib;

class Components extends BooklyLib\Base\Component
{
    /**
     * Render license required form.
     *
     * @param bool $bookly_page
     */
    public static function renderLicenseRequired( $bookly_page )
    {
        if ( $bookly_page && ( Lib\Config::graceExpired() || get_user_meta( get_current_user_id(), 'bookly_grace_hide_admin_notice_time', true ) < time() ) ) {
            $remaining_days = Lib\Config::graceRemainingDays();
            if ( $remaining_days !== false ) {
                $role = Common::isCurrentUserAdmin() ? 'admin' : 'staff';
                self::_enqueueAssets();
                self::renderLicenseDialog();
                if ( $remaining_days > 0 ) {
                    // Grace has started.
                    $days_text = array( '{days}' => sprintf( _n( '%d day', '%d days', $remaining_days, 'bookly' ), $remaining_days ) );
                    self::renderTemplate( 'board', array(
                        'board_body' => self::renderTemplate( $role . '_grace', compact( 'days_text' ), false ),
                    ) );
                } else {
                    // Grace expired.
                    self::renderTemplate( 'board', array(
                        'board_body' => self::renderTemplate( $role . '_grace_ended', array(), false ),
                    ) );
                }
            }
        }
    }

    /**
     * Render license notice.
     *
     * @param bool $bookly_page
     */
    public static function renderLicenseNotice( $bookly_page )
    {
        // Checking if notice is 'rendered' in the current request
        if ( ! self::hasInCache( __FUNCTION__ ) ) {
            if ( ! $bookly_page && get_user_meta( get_current_user_id(), 'bookly_grace_hide_admin_notice_time', true ) < time() ) {
                $remaining_days = Lib\Config::graceRemainingDays();
                if ( $remaining_days !== false ) {
                    $role = Common::isCurrentUserAdmin() ? 'admin' : 'staff';
                    self::_enqueueAssets();
                    if ( $remaining_days > 0 ) {
                        $replace_data = array(
                            '{url}'  => Common::escAdminUrl( Shop\Page::pageSlug() ),
                            '{days}' => sprintf( _n( '%d day', '%d days', $remaining_days, 'bookly' ), $remaining_days ),
                        );
                        self::renderTemplate( $role . '_notice_grace', compact( 'replace_data' ) );
                    } else {
                        $replace_data = array(
                            '{url}' => Common::escAdminUrl( Shop\Page::pageSlug() ),
                        );
                        self::renderTemplate( $role . '_notice_grace_ended', compact( 'replace_data' ) );
                    }
                }
            }
        }
        self::putInCache( __FUNCTION__, 'rendered' );
    }

    /**
     * Render purchase reminder.
     *
     * @param bool $bookly_page
     */
    public static function renderPurchaseReminder( $bookly_page )
    {
        if ( $bookly_page && get_user_meta( get_current_user_id(), 'bookly_show_purchase_reminder', true ) ) {
            self::renderTemplate( 'purchase_reminder' );
        }
    }

    /**
     * @param string $slug
     * @return void
     */
    public static function manageLicense( $slug )
    {
        $addon = BooklyLib\Entities\Shop::query()
            ->where( 'slug', $slug )
            ->findOne();

        $addon_pc_name = self::getOptionName( $slug );
        if ( $addon ) {
            $licensed_bundles = BooklyLib\Entities\Shop::query()
                ->whereNot( 'bundle_plugins', null )
                ->whereNot( 'id', $addon->getId() )
                ->find() ?: array();
            $licensed_addons = array();
            foreach ( $licensed_bundles as $bundle ) {
                $pc = get_option( self::getOptionName( $bundle->getSlug() ) );
                if ( $pc ) {
                    $_addons = BooklyLib\Entities\Shop::query()
                        ->whereIn( 'plugin_id', json_decode( $bundle->getBundlePlugins(), true ) )
                        ->find();
                    foreach ( $_addons as $_addon ) {
                        $licensed_addons[ $_addon->getPluginId() ] = $pc;
                    }
                }
            }
            $plugins_to_detach = BooklyLib\Entities\Shop::query()
                ->whereIn( 'plugin_id', $addon->getBundlePlugins()
                    ? json_decode( $addon->getBundlePlugins(), true )
                    : array( $addon->getPluginId() )
                )
                ->find() ?: array();
            /** @var BooklyLib\Entities\Shop $plugin_to_detach */
            foreach ( $plugins_to_detach as $plugin_to_detach ) {
                $option_name = self::getOptionName( $plugin_to_detach->getSlug() );
                if ( ! get_option( $option_name, false ) || get_option( $addon_pc_name ) == get_option( $option_name, false ) ) {
                    $value = isset( $licensed_addons[ $plugin_to_detach->getPluginId() ] ) ? $licensed_addons[ $plugin_to_detach->getPluginId() ] : '';
                    update_option( $option_name, $value );
                }
            }
        }

        update_option( $addon_pc_name, '' );
    }

    private static function getOptionName( $slug )
    {
        return str_replace( array( '-addon', '-' ), array( '', '_' ), $slug ) . '_purchase_code';
    }

    /**
     * Enqueue assets.
     */
    private static function _enqueueAssets()
    {
        self::enqueueStyles( array(
            'module' => array( 'css/license.css' ),
            'bookly' => array( 'backend/resources/css/fontawesome-all.min.css' => array( 'bookly-backend-globals' ), ),
        ) );

        self::enqueueScripts( array(
            'module' => array( 'js/license.js' => array( 'bookly-backend-globals' ), ),
        ) );
    }

    public static function renderLicenseDialog()
    {
        self::enqueueStyles( array(
            'bookly' => array(
                'backend/resources/css/fontawesome-all.min.css' => array( 'bookly-backend-globals' ),
                'backend/resources/tailwind/tailwind.css' => array( 'bookly-backend-globals' ),
            ),
        ) );

        self::enqueueScripts( array(
            'module' => array( 'js/license-dialog.js' => array( 'bookly-backend-globals' ), ),
        ) );

        wp_localize_script( 'bookly-license-dialog.js', 'BooklyL10nLicenseDialog', array(
            'l10n' => array(
                'addon_statuses' => array(
                    'active' => __( 'Done', 'bookly' ),
                    'error' => __( 'Error', 'bookly' ),
                    'installed' => __( 'Installed', 'bookly' ),
                    'not_installed' => __( 'Not installed', 'bookly' ),
                    'processing' => __( 'Processing', 'bookly' ) . '…',
                ),
                'action_required' => __( 'Action required', 'bookly' ),
                'activate_purchase_code' => __( 'Activate purchase code', 'bookly' ),
                'addons_in_trial' => __( 'Add-ons in Trial Period', 'bookly' ),
                'apply' => __( 'Apply', 'bookly' ),
                'close' => __( 'Close', 'bookly' ),
                'enter_purchase_code' => __( 'Enter purchase code', 'bookly' ),
                'trial_period_info' => __( 'During the trial period, you can use the add-ons without entering a purchase code. Once the trial ends, purchase code activation is required.', 'bookly' ),
                'i_have_another_purchase_code' => __( 'I have another purchase code', 'bookly' ),
                'in_use_purchase_code' => __( 'This purchase code is already in use on another site', 'bookly' ),
                'included_addons' => __( 'Included add-ons', 'bookly' ),
                'invalid_purchase_code_info1' => __( 'The purchase code you entered is not valid.', 'bookly' ) . ' ' . __( 'Please check the code and try again.', 'bookly' ),
                'invalid_purchase_code_info2' => sprintf( __( 'If the problem persists, contact our support at %s.', 'bookly' ), '<a href="mailto:support@bookly.net">support@bookly.net</a>' ),
                'more' => __( '+%d more', 'bookly' ),
                'purchase_code' => __( 'Purchase code', 'bookly' ),
                'purchase_code_help' => __( 'Enter your purchase code for a Bookly add-on or bundle', 'bookly' ),
                'verification_unavailable' => __( 'Purchase code verification is temporarily unavailable. Please try again later.', 'bookly' ),
            ),
        ) );
    }
}