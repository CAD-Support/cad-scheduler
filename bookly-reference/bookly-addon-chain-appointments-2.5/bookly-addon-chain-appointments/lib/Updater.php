<?php
namespace BooklyChainAppointments\Lib;

class Updater extends \Bookly\Lib\Base\Updater
{
    public function update_2_5()
    {
        $new_pc_key = 'bookly_chain_appointments_purchase_code';
        $old_pc_key = 'bookly_chain_appointments_envato_purchase_code';
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
        $this->addL10nOptions( array(
            'bookly_l10n_chain_appointments_book_more'     => __( 'Add service', 'bookly' ),
        ) );
    }
}