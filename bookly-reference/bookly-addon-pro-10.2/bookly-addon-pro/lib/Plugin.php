<?php
namespace BooklyPro\Lib;

use Bookly\Lib as BooklyLib;
use BooklyPro\Backend;
use BooklyPro\Frontend;

abstract class Plugin extends BooklyLib\Base\Plugin
{
    protected static $prefix;
    protected static $title;
    protected static $version;
    protected static $slug;
    protected static $directory;
    protected static $main_file;
    protected static $basename;
    protected static $text_domain;
    protected static $root_namespace;
    protected static $embedded;

    /**
     * @inheritDoc
     */
    protected static function init()
    {
        Backend\Components\Gutenberg\AppointmentsList\Block::init();
        Backend\Components\Gutenberg\Calendar\Block::init();
        Backend\Components\Gutenberg\Shortcodes\Block::init();

        // Register proxy methods.
        Backend\Components\Dialogs\Appointment\AttachPayment\ProxyProviders\Local::init();
        Backend\Components\Dialogs\Appointment\CustomerDetails\ProxyProviders\Shared::init();
        Backend\Components\Dialogs\Appointment\Edit\ProxyProviders\Local::init();
        Backend\Components\Dialogs\Appointment\Edit\ProxyProviders\Shared::init();
        Backend\Components\Dialogs\Customer\ProxyProviders\Shared::init();
        Backend\Components\Dialogs\Payment\ProxyProviders\Shared::init();
        Backend\Components\Dialogs\Service\Edit\ProxyProviders\Local::init();
        Backend\Components\Dialogs\Service\Edit\ProxyProviders\Shared::init();
        Backend\Components\Dialogs\Staff\ProxyProviders\Local::init();
        Backend\Components\Dialogs\Staff\Edit\ProxyProviders\Local::init();
        Backend\Components\Dialogs\Staff\Edit\ProxyProviders\Shared::init();
        Backend\Components\Notices\ProxyProviders\Local::init();
        Backend\Components\TinyMce\ProxyProviders\Shared::init();
        Backend\Modules\Appearance\ProxyProviders\Local::init();
        Backend\Modules\Appearance\ProxyProviders\Shared::init();
        Backend\Modules\Appointments\ProxyProviders\Local::init();
        Backend\Modules\Calendar\ProxyProviders\Local::init();
        Backend\Modules\Calendar\ProxyProviders\Shared::init();
        Backend\Modules\Customers\ProxyProviders\Local::init();
        Backend\Modules\Dashboard\ProxyProviders\Local::init();
        Backend\Modules\Notifications\ProxyProviders\Local::init();
        Backend\Modules\Notifications\ProxyProviders\Shared::init();
        Backend\Modules\Services\ProxyProviders\Shared::init();
        Backend\Modules\Settings\ProxyProviders\Local::init();
        Backend\Modules\Settings\ProxyProviders\Shared::init();
        Backend\Modules\Setup\ProxyProviders\Local::init();
        Backend\Modules\Staff\ProxyProviders\Local::init();
        Backend\Modules\Staff\ProxyProviders\Shared::init();
        Frontend\Modules\Booking\ProxyProviders\Local::init();
        Frontend\Modules\Booking\ProxyProviders\Shared::init();
        Frontend\Modules\Calendar\ShortCode::init();
        Notifications\Assets\Combined\ProxyProviders\Local::init();
        Notifications\Assets\Item\ProxyProviders\Shared::init();
        Notifications\Assets\Order\ProxyProviders\Shared::init();
        Notifications\Cart\ProxyProviders\Local::init();
        Notifications\Test\ProxyProviders\Shared::init();
        Payment\ProxyProviders\Shared::init();
        ProxyProviders\Local::init();
        ProxyProviders\Shared::init();

        $wc_loaded = class_exists( 'WooCommerce', false );
        // Register hooks when WooCommerce class loaded.
        // To protect against themes which call WooCommerce hooks when WC not used.
        if ( $wc_loaded && get_option( 'bookly_wc_enabled' ) ) {
            Frontend\Modules\WooCommerce\Controller::init();
        }

        if ( ! is_admin() ) {
            // Init short code.
            Frontend\Modules\CancellationConfirmation\ShortCode::init();
            Frontend\Modules\CustomerProfile\ShortCode::init();
            Frontend\Modules\SearchForm\ShortCode::init();
            Frontend\Modules\ServicesForm\ShortCode::init();
            Frontend\Modules\StaffForm\ShortCode::init();
            Frontend\Modules\TagsForm\ShortCode::init();
            Frontend\Modules\CheckoutForm\ShortCode::init();
        }
    }

    /**
     * @inerhitDoc
     */
    protected static function registerAjax()
    {
        Backend\Components\Dialogs\Payment\Ajax::init();
        Backend\Components\Dialogs\Service\Edit\Ajax::init();
        Backend\Components\Dialogs\Service\Tags\Ajax::init();
        Backend\Components\Dialogs\Staff\Categories\Ajax::init();
        Backend\Components\Dialogs\Staff\Duplicate\Ajax::init();
        Backend\Components\Dialogs\Staff\Edit\Ajax::init();
        Backend\Components\License\Ajax::init();
        Backend\Modules\Appearance\Ajax::init();
        Backend\Modules\Appointments\Ajax::init();
        Backend\Modules\Customers\Ajax::init();
        Backend\Modules\Notifications\Ajax::init();
        Backend\Modules\Settings\Ajax::init();
        Backend\Modules\Staff\Ajax::init();
        Frontend\Modules\Booking\Ajax::init();
        Frontend\Modules\CustomerProfile\Ajax::init();
        Frontend\Modules\Icalendar\Ajax::init();
        Frontend\Modules\CheckoutForm\Ajax::init();
        Frontend\Modules\ModernBookingForm\Ajax::init();
        Frontend\Modules\WooCommerce\Ajax::init();

        if ( get_option( 'bookly_cal_frontend_enabled' ) ) {
            Frontend\Modules\Calendar\Ajax::init();
        }
    }

    /**
     * @inheritDoc
     */
    public static function run()
    {
        // l10n. Pro owns the shared 'bookly' text domain for the core plugin's
        // add-ons (the core plugin moved to the 'bookly-responsive-appointment-booking-tool' domain).
        load_plugin_textdomain( 'bookly', false, self::getSlug() . '/languages' );

        // Divi
        add_action( 'et_builder_ready', function () {
            new Backend\Components\Divi\SearchForm\SearchForm();
            new Backend\Components\Divi\ServicesForm\ServicesForm();
            new Backend\Components\Divi\StaffForm\StaffForm();
        } );

        parent::run();

        // Run embedded products.
        foreach ( self::embeddedProducts() as $plugin_class ) {
            $plugin_class::run();
        }
    }

    /**
     * @inheritDoc
     */
    public static function uninstall( $network_wide )
    {
        // Uninstall embedded products.
        foreach ( self::embeddedProducts() as $plugin_class ) {
            $plugin_class::uninstall( $network_wide );
        }

        parent::uninstall( $network_wide );
    }

    /**
     * @inheritDoc
     */
    public static function activate( $network_wide )
    {
        parent::activate( $network_wide );

        if ( ! $network_wide ) {
            // Activate embedded products.
            foreach ( self::embeddedProducts() as $plugin_class ) {
                $plugin_class::activate( false );
            }
        }
    }

    /**
     * Get embedded products.
     *
     * @return BooklyLib\Base\Plugin[]
     */
    protected static function embeddedProducts()
    {
        $result = array();
        $dir = self::getDirectory() . '/lib/addons/';
        $licensed_products = get_option( 'bookly_pro_licensed_products' );
        if ( $licensed_products === 'undefined' && self::getPurchaseCode() != '' ) {
            $class = get_called_class();
            $pc_check_result = API::verifyPurchaseCode( self::getPurchaseCode(), $class::getSlug() );
            if ( $pc_check_result['valid'] ) {
                $licensed_products = get_option( 'bookly_pro_licensed_products' ) ?: array();
            }
        }

        $licensed_products = is_array( $licensed_products )
            ? $licensed_products
            : array();


        foreach ( $licensed_products as $slug ) {
            if ( $addon = self::loadAddon( $dir, $slug ) ) {
                $result[] = $addon;
            }
        }

        return $result;
    }

    protected static function loadAddon( $dir, $slug )
    {
        $autoload = $dir . $slug . '/autoload.php';
        if ( is_readable( $autoload ) ) {
            $namespace = implode( '', array_map( 'ucfirst', explode( '-', str_replace( '-addon-', '-', $slug ) ) ) );
            if ( ! class_exists( '\\' . $namespace . '\Lib\Boot' ) ) {
                include_once $autoload;

                /** @var BooklyLib\Base\Plugin $plugin */
                $plugin = '\\' . $namespace . '\Lib\Plugin';

                $data_loaded_option_name = $plugin::getPrefix() . 'data_loaded';
                if ( ! get_option( $data_loaded_option_name ) ) {
                    $installer_class = '\\' . $namespace . '\Lib\Installer';
                    /** @var BooklyLib\Base\Installer $installer */
                    $installer = new $installer_class();
                    $installer->install();
                }

                return $plugin;
            }
        }

        return false;
    }
}