<?php
namespace BooklyAdvancedGoogleCalendar\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_gc_full_sync_offset_days_before' => '0',
            'bookly_gc_full_sync_offset_days_after' => '365',
            'bookly_gc_full_sync_titles' => '1',
            'bookly_gc_force_update_description' => '0',
        );
    }
}