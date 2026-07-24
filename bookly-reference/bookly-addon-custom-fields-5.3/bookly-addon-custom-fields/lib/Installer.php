<?php
namespace BooklyCustomFields\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_custom_fields_data'            => '[]',
            'bookly_custom_fields_per_service'     => '0',
            'bookly_custom_fields_merge_repeating' => '1',
        );
    }
}