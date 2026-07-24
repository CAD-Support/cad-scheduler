<?php
namespace BooklyCustomStatuses\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Backend\Components\Settings\Menu;
use Bookly\Lib as BooklyLib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function enqueueAssets()
    {
        self::enqueueScripts( array(
            'module' => array( 'js/custom_statuses.js' => array( 'bookly-backend-globals' ) ),
        ) );

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::CUSTOM_STATUSES );

        wp_localize_script( 'bookly-custom_statuses.js', 'BooklyCustomStatusesL10n', array(
            'areYouSure' => __( 'Are you sure?', 'bookly' ),
            'zeroRecords' => __( 'No statuses found.', 'bookly' ),
            'add_status' => __( 'Add status', 'bookly' ) . '…',
            'delete' => __( 'Delete', 'bookly' ) . '…',
            'order' => _x( 'Order', 'drag to reorder', 'bookly' ) . '…',
            'order_title' => __( 'Custom statuses order', 'bookly' ),
            'order_hint' => __( 'Adjust the order of custom statuses', 'bookly' ),
            'search' => __( 'Quick search by name', 'bookly' ) . '…',
            'free' => __( 'Free', 'bookly' ),
            'busy' => __( 'Busy', 'bookly' ),
            'datatables' => $datatables,
        ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderMenuItem()
    {
        Menu::renderItem( __( 'Custom Statuses', 'bookly' ), 'custom_statuses' );
    }

    /**
     * @inheritDoc
     */
    public static function saveSettings( array $alert, $tab, array $params )
    {
        if ( $tab == 'calendar' ) {
            $statuses = \BooklyCustomStatuses\Lib\ProxyProviders\Local::getAll();
            foreach ( $params['status'] as $status => $color ) {
                if ( array_key_exists( $status, $statuses ) ) {
                    $statuses[ $status ]->setColor( $color )->save();
                }
            }
        }

        return $alert;
    }
}