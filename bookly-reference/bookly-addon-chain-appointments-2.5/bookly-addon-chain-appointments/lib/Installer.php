<?php
namespace BooklyChainAppointments\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_chain_appointments_enabled' => '1',
            'bookly_l10n_chain_appointments_book_more' => __( 'Add service', 'bookly' ),
        );
    }

}