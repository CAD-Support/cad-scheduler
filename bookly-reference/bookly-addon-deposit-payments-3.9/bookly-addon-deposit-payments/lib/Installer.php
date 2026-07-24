<?php
namespace BooklyDepositPayments\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = array(
            'bookly_deposit_allow_full_payment' => '0',
            'bookly_l10n_info_deposit' => __( 'Would you like to pay deposit or total price', 'bookly' ),
            'bookly_l10n_label_deposit_payment' => __( 'I will pay deposit', 'bookly' ),
            'bookly_l10n_label_full_payment' => __( 'I will pay total price', 'bookly' ),
        );
    }
}