<?php
namespace BooklyAdvancedGoogleCalendar\Lib;

use Bookly\Lib as BooklyLib;

class Updater extends BooklyLib\Base\Updater
{
    public function update_3_0()
    {
        $new_pc_key = 'bookly_advanced_google_calendar_purchase_code';
        $old_pc_key = 'bookly_advanced_google_calendar_envato_purchase_code';
        $current_pc = get_option( $old_pc_key, 'missing' );
        if ( $current_pc === 'missing' ) {
            add_option( $new_pc_key, '' );
        } else {
            if ( $current_pc ) {
                add_option( $new_pc_key, $current_pc );
            }
            delete_option( $old_pc_key );
        }
    }

    public function update_1_8()
    {
        $this->renameOptions( array( 'bookly_gc_full_sync_offset_days' => 'bookly_gc_full_sync_offset_days_before' ) );
        add_option( 'bookly_gc_full_sync_offset_days_after', 365 );
    }

    public function update_1_7()
    {
        add_option( 'bookly_gc_force_update_description', '0' );
    }

    public function update_1_1()
    {
        delete_option( 'bookly_advanced_google_calendar_enabled' );
    }
}