<?php
namespace BooklyDiscounts\Backend\Modules\Discounts;

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
                'js/discounts.js' => array( 'bookly-backend-globals' ),
            ),
        ) );

        $services = BooklyLib\Entities\Service::query()
            ->select( 'id, title' )
            ->indexBy( 'id' )
            ->whereNot( 'type', BooklyLib\Entities\Service::TYPE_PACKAGE )
            ->fetchArray();

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::DISCOUNTS );

        wp_localize_script( 'bookly-discounts.js', 'BooklyDiscountsL10n', array(
            'csrfToken' => BooklyLib\Utils\Common::getCsrfToken(),
            'edit' => esc_html__( 'Edit', 'bookly' ),
            'new_discount' => __( 'New discount', 'bookly' ) . '…',
            'delete' => __( 'Delete', 'bookly' ) . '…',
            'search' => __( 'Quick search by title', 'bookly' ) . '…',
            'are_you_sure' => esc_html__( 'Are you sure?', 'bookly' ),
            'zeroRecords' => esc_html__( 'No discounts found.', 'bookly' ),
            'services' => array(
                'allSelected' => __( 'All services', 'bookly' ),
                'nothingSelected' => __( 'No service selected', 'bookly' ),
                'collection' => $services,
                'count' => count( $services ),
            ),
            'datatables' => $datatables,
            'state' => array(
                esc_html__( 'Disabled', 'bookly' ),
                esc_html__( 'Enabled', 'bookly' ),
            ),
        ) );

        self::renderTemplate( 'index' );
    }
}