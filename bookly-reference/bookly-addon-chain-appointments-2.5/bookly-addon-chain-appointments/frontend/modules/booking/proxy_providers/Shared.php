<?php
namespace BooklyChainAppointments\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Frontend\Modules\Booking\Proxy;
use Bookly\Lib as BooklyLib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function stepOptions( $options, $step, $userData )
    {
        if ( $step == 'service' ) {
            $options['multi_service'] = true;
            $options['l10n']['add_service'] = BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_chain_appointments_book_more' );
        }

        return $options;
    }
}