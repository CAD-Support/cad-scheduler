<?php
namespace BooklyCustomStatuses\Backend\Modules\Settings;

use BooklyCustomStatuses\Lib;
use Bookly\Lib as BooklyLib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Get list of statuses.
     */
    public static function getStatuses()
    {
        $rows = Lib\Entities\CustomStatus::query()
            ->sortBy( 'position' )
            ->fetchArray();

        wp_send_json_success( $rows );
    }

    /**
     * Update statuses position.
     */
    public static function updateStatusesPosition()
    {
        $statuses_sort = (array) self::parameter( 'positions' );
        foreach ( $statuses_sort as $position => $id ) {
            $status = new Lib\Entities\CustomStatus();
            $status->load( $id );
            $status->setPosition( $position );
            $status->save();
        }

        wp_send_json_success();
    }

    /**
     * Remove status(es).
     */
    public static function deleteStatuses()
    {
        $ids = self::parameter( 'ids', array() );

        // Update customer_appointment statuses accordingly.
        BooklyLib\Entities\CustomerAppointment::query( 'ca' )
            ->update()
            ->setRaw( 'status = IF(cs.busy, "approved", "cancelled")', array() )
            ->leftJoin( 'CustomStatus', 'cs', 'ca.status = cs.slug', '\BooklyCustomStatuses\Lib\Entities' )
            ->whereIn( 'cs.id', $ids )
            ->execute()
        ;

        // Delete custom statuses.
        foreach ( Lib\Entities\CustomStatus::query()->whereIn( 'id', $ids )->find() as $status ) {
            $status->delete();
        }

        wp_send_json_success();
    }

    /**
     * Save status.
     */
    public static function saveStatus()
    {
        $params = self::parameters();

        // Generate unique slug.
        $slug = sanitize_key( preg_replace( '/\s+/', '-', $params['name'] ) );
        $tail = '';
        $i    = 0;
        if ( $slug == '' ) {
            $slug = 'status';
            $tail = '-1';
            $i    = 1;
        }
        $id = isset ( $params['id'] ) ? $params['id'] : 0;
        while ( Lib\Entities\CustomStatus::query()->where( 'slug', $slug . $tail )->whereNot( 'id', $id )->count() ) {
            ++ $i;
            $tail = "-$i";
        }
        $params['slug'] = $slug . $tail;

        $form = new Forms\CustomStatus();
        $form->bind( $params );
        $status = $form->save();

        wp_send_json_success( $status->getFields() );
    }
}