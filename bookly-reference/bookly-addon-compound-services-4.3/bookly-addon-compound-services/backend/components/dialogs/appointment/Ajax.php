<?php
namespace BooklyCompoundServices\Backend\Components\Dialogs\Appointment;

use Bookly\Lib as BooklyLib;
use BooklyCompoundServices\Lib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return BooklyLib\Config::staffCabinetActive() ? array( '_default' => array( 'staff', 'supervisor' ) ) : array();
    }

    /**
     * Get json with appointments for compound service.
     */
    public static function getAppointments()
    {
        $compound_token = array_key_exists( 'compound_token', $_GET ) ? $_GET['compound_token'] : 0;
        $appointments   = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
            ->select( 'a.id, a.staff_any, a.start_date, st.full_name AS staff_name, s.title service, ca.compound_service_id' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->leftJoin( 'Service', 's', 's.id = a.service_id' )
            ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
            ->where( 'ca.compound_token', $compound_token )
            ->sortBy( 'a.start_date' )
            ->fetchArray();
        $service_name = '';
        if ( $appointments ) {
            $service_name = BooklyLib\Entities\Service::find( $appointments[0]['compound_service_id'] )->getTitle();
        }

        wp_send_json_success( compact( 'service_name', 'appointments' ) );
    }
}