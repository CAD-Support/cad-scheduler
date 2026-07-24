<?php
namespace BooklyPro\Backend\Components\License;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array(
            'dismissPurchaseReminder' => array( 'staff', 'supervisor' ),
            'hideGraceNotice' => array( 'staff', 'supervisor' ),
            'detachPurchaseCode' => array( 'supervisor' ),
        );
    }

    /**
     * Render form for verification purchase codes.
     */
    public static function verifyPurchaseCodeForm()
    {
        wp_send_json_success( array( 'html' => self::renderTemplate( 'verification', array(), false ) ) );
    }

    /**
     * Purchase code info.
     */
    public static function getPurchaseCodeInfo()
    {
        $purchase_code = preg_replace( '/[ \t\x00-\x1F\x7F-\xFF]/', '', self::parameter( 'purchase_code' ) );
        wp_send_json( Lib\API::getPurchaseCodeInfo( $purchase_code, self::parameter( 'blog_id' ) ) );
    }

    /**
     * Detach purchase code.
     */
    public static function detachPurchaseCode()
    {
        $slug = self::parameter( 'slug' );
        $blog_id = is_multisite() ? self::parameter( 'blog_id' ) : null;

        if ( $blog_id ) {
            switch_to_blog( $blog_id );
        }

        Lib\API::detachPurchaseCode( get_option( str_replace( array( '-addon', '-' ), array( '', '_' ), $slug ) . '_purchase_code', false ), $blog_id );
        Components::manageLicense( $slug );

        wp_send_json_success();
    }

    public static function getAddonsState()
    {
        $data = array(
            'addons' => self::getInstalledBooklyAddonsState(),
        );

        wp_send_json_success( $data );
    }

    /**
     * Support until date verification.
     */
    public static function reCheckSupport()
    {
        /** @var BooklyLib\Base\Plugin $plugin_class */
        $plugin_class = self::parameter( 'plugin' ) . '\Lib\Plugin';
        $purchase_code = $plugin_class::getPurchaseCode();
        wp_send_json( Lib\API::verifySupport( $purchase_code, $plugin_class ) );
    }

    /**
     * One hour no show message License Required.
     */
    public static function hideGraceNotice()
    {
        update_user_meta( get_current_user_id(), 'bookly_grace_hide_admin_notice_time', strtotime( 'tomorrow' ) );
        wp_send_json_success();
    }

    /**
     * Render window with message license verification succeeded.
     */
    public static function verificationSucceeded()
    {
        wp_send_json_success( array( 'html' => self::renderTemplate( 'verification_succeeded', array(), false ) ) );
    }

    /**
     * Dismiss purchase reminder.
     */
    public static function dismissPurchaseReminder()
    {
        delete_user_meta( get_current_user_id(), 'bookly_show_purchase_reminder' );
    }

    /**
     * Deactivate Bookly Pro add-on
     */
    public static function deactivate()
    {
        deactivate_plugins( array( 'bookly-addon-pro/main.php' ) );
        wp_send_json_success( array( 'target' => BooklyLib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Calendar\Page::pageSlug() ) ) );
    }

    public static function applyBooklyLicense()
    {
        $plugin_slug_for_activation = self::parameter( 'slug' );

        if ( strpos( $plugin_slug_for_activation, 'bookly-addon-' ) === false ) {
            wp_send_json_error();
        }

        $purchase_code = preg_replace( '/[ \t\x00-\x1F\x7F-\xFF]/', '', self::parameter( 'purchase_code' ) );
        $blog_id = is_multisite() ? self::parameter( 'blog_id' ) : null;
        if ( $blog_id ) {
            switch_to_blog( $blog_id );
        }

        $result = Lib\API::verifyPurchaseCode( $purchase_code, $plugin_slug_for_activation );
        if ( $result['valid'] ) {
            self::savePurchaseCode( $plugin_slug_for_activation, self::parameter( 'license_slug' ), $purchase_code );

            if ( ! is_multisite() ) {
                $installed = self::getInstalledBooklyAddonsState();
                if ( ! isset( $installed[ $plugin_slug_for_activation ] ) ) {
                    if ( ! self::installAndActivatePlugin( $plugin_slug_for_activation, add_query_arg( compact( 'purchase_code' ), self::parameter( 'url' ) ) ) ) {
                        wp_send_json_error();
                    }
                }
            }
        } else {
            wp_send_json_error();
        }

        wp_send_json_success();
    }

    private static function getInstalledBooklyAddonsState()
    {
        $installed_bookly_addons = array();
        $plugins_dir = dirname( dirname( Lib\Plugin::getMainFile() ) ) . '/';
        $bookly_plugins = apply_filters( 'bookly_plugins', array() );
        foreach ( glob( $plugins_dir . 'bookly-addon-*', GLOB_ONLYDIR ) as $path ) {
            $slug = wp_basename( $path );
            $data = array(
                'active' => isset( $bookly_plugins[ $slug ] ),
                'pc_exists' => false,
                'gs' => 0,
                'title' => '',
            );

            if ( $data['active'] ) {
                $prefix = str_replace( array( '-addon', '-' ), array( '', '_' ), $slug );
                $data['pc_exists'] = get_option( $prefix . '_purchase_code', false ) != false;
                if ( ! $data['pc_exists'] ) {
                    $data['gs'] = (int) get_option( $prefix . '_grace_start' );
                }
                $data['title'] = $bookly_plugins[ $slug ]::getTitle();
            }

            $installed_bookly_addons[ $slug ] = $data;
        }

        return $installed_bookly_addons;
    }

    /**
     * @param string $plugin_slug_for_activation
     * @param string $license_slug
     * @param string $purchase_code
     */
    private static function savePurchaseCode( $plugin_slug_for_activation, $license_slug, $purchase_code )
    {
        $is_bundle = $plugin_slug_for_activation !== $license_slug;
        $plugins = self::getAffectedPlugins( $is_bundle, $plugin_slug_for_activation, $license_slug );

        $option_name = str_replace( array( '-addon', '-' ), array( '', '_' ), $plugin_slug_for_activation ) . '_purchase_code';
        update_option( $option_name, $purchase_code );

        if ( $is_bundle ) {
            $option_name = str_replace( array( '-addon', '-' ), array( '', '_' ), $license_slug ) . '_purchase_code';
            update_option( $option_name, $purchase_code );
        }
    }

    /**
     * @param string $plugin_slug_for_activation
     * @param string $license_slug
     * @return array
     */
    private static function getAffectedPlugins( $is_bundle, $plugin_slug_for_activation, $license_slug )
    {
        $query_affected_plugins = BooklyLib\Entities\Shop::query()
            ->whereIn( 'slug', array( $plugin_slug_for_activation, $license_slug ) )
            ->indexBy( 'slug' );

        /** @var BooklyLib\Entities\Shop[] $plugins */
        $plugins = $query_affected_plugins->find();

        if ( empty( $plugins ) || ( $is_bundle && count( $plugins ) < 2 ) ) {
            BooklyLib\Routines::handleDailyInfo();
            $plugins = $query_affected_plugins->find();
        }

        return $plugins;
    }

    /**
     * @param string $slug
     * @param string $url
     * @return string
     */
    private static function addDependenceToUrl( $slug, $url )
    {
        $dependence = Lib\Plugin::getVersion();
        if ( $slug === 'bookly-addon-pro' ) {
            $dependence = BooklyLib\Plugin::getVersion();
        }

        return add_query_arg( array( 'site_url' => site_url(), 'dependence' => $dependence ), $url );
    }

    /**
     * @param string $slug
     * @param string $url
     * @return bool
     */
    private static function installAndActivatePlugin( $slug, $url )
    {
        if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        $url = self::addDependenceToUrl( $slug, $url );

        $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
        $state = $upgrader->install( $url );

        if ( $state === true ) {
            activate_plugin( $slug . '/main.php' );
        }

        return $state === true;
    }
}