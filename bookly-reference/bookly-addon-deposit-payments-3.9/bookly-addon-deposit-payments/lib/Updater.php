<?php
namespace BooklyDepositPayments\Lib;

class Updater extends \Bookly\Lib\Base\Updater
{
    public function update_3_9()
    {
        $new_pc_key = 'bookly_deposit_payments_purchase_code';
        $old_pc_key = 'bookly_deposit_payments_envato_purchase_code';
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

    public function update_3_5()
    {
        add_option( 'bookly_deposit_allow_full_payment', '0' );
    }

    public function update_2_1()
    {
        delete_option( 'bookly_deposit_payments_enabled' );
    }

    public function update_2_0()
    {
        $this->addL10nOptions( array(
            'bookly_l10n_info_deposit'          => __( 'Would you like to pay deposit or total price', 'bookly' ),
            'bookly_l10n_label_deposit_payment' => __( 'I will pay deposit', 'bookly' ),
            'bookly_l10n_label_full_payment'    => __( 'I will pay total price', 'bookly' ),
        ) );
    }
}