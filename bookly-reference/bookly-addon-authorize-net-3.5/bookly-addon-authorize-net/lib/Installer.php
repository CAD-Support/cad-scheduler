<?php
namespace BooklyAuthorizeNet\Lib;

use Bookly\Lib as BooklyLib;

class Installer extends Base\Installer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $status = get_option( 'bookly_pmt_authorize_net', '0' );
        $this->options = array(
            'bookly_authorize_net_enabled' => $status == 'disabled' ? '0' : $status,
            'bookly_authorize_net_api_login_id' => get_option( 'bookly_pmt_authorize_net_api_login_id' ),
            'bookly_authorize_net_transaction_key' => get_option( 'bookly_pmt_authorize_net_transaction_key' ),
            'bookly_authorize_net_sandbox' => get_option( 'bookly_pmt_authorize_net_sandbox', '0' ),
            'bookly_authorize_net_increase' => '0',
            'bookly_authorize_net_addition' => '0',
            'bookly_authorize_net_send_tax' => '0',
            'bookly_authorize_net_timeout' => '0',
            'bookly_l10n_label_pay_authorize_net' => __( 'I will pay now with Credit Card', 'bookly' ),
        );

        $deprecated = array(
            'bookly_pmt_authorize_net',
            'bookly_pmt_authorize_net_api_login_id',
            'bookly_pmt_authorize_net_transaction_key',
            'bookly_pmt_authorize_net_sandbox',
        );
        foreach ( $deprecated as $option_name ) {
            delete_option( $option_name );
        }
    }
}