<?php
namespace BooklyAuthorizeNet\Lib;

class Updater extends \Bookly\Lib\Base\Updater
{
    public function update_3_4()
    {
        $new_pc_key = 'bookly_authorize_net_purchase_code';
        $old_pc_key = 'bookly_authorize_net_envato_purchase_code';
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

    public function update_3_1()
    {
        add_option( 'bookly_authorize_net_timeout', '0' );
    }

    public function update_1_9()
    {
        $this->addL10nOptions( array( 'bookly_l10n_label_pay_authorize_net' => __( 'I will pay now with Credit Card', 'bookly' ) ) );
    }

    public function update_1_2()
    {
        add_option( 'bookly_authorize_net_send_tax', '0' );
    }

    public function update_1_1()
    {
        add_option( 'bookly_authorize_net_increase', '0' );
        add_option( 'bookly_authorize_net_addition', '0' );
    }
}