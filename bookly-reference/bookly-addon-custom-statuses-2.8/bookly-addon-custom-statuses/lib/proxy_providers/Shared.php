<?php
namespace BooklyCustomStatuses\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * Add the Custom Statuses tab to the Settings submenu in the fullscreen header sidebar.
     *
     * @param array $submenus
     * @return array
     */
    public static function buildHeaderSubmenus( $submenus )
    {
        if ( isset( $submenus['bookly-settings'] ) ) {
            $submenus['bookly-settings'][] = array( 'label' => __( 'Custom Statuses', 'bookly' ), 'tab' => 'custom_statuses', 'badge' => '' );
        }

        return $submenus;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableColumns( $columns, $table )
    {
        if ( $table == BooklyLib\Utils\Tables::CUSTOM_STATUSES ) {
            $columns = array_merge( $columns, array(
                'id' => esc_html__( 'ID', 'bookly' ),
                'name' => esc_html__( 'Name', 'bookly' ),
                'busy' => esc_html__( 'Free/Busy', 'bookly' ),
            ) );
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableDefaultSettings( $columns, $table )
    {
        if ( $table == BooklyLib\Utils\Tables::CUSTOM_STATUSES ) {
            $columns = array_merge( $columns, array(
                'id' => false,
            ) );
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public static function prepareColorsStatuses( array $statuses )
    {
        foreach ( Local::getAll() as $status ) {
            $statuses[ $status->getSlug() ] = $status->getColor();
        }

        return $statuses;
    }
}