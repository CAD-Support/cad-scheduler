<?php
namespace BooklyCollaborativeServices\Backend\Components\Dialogs\Appointment;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\CustomerAppointment;

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
     * Get json with appointments for collaborative service.
     */
    public static function getAppointments()
    {
        $collaborative_token = self::parameter( 'collaborative_token', 0 );
        $appointments = CustomerAppointment::query( 'ca' )
            ->select( 'a.id, a.staff_any, a.start_date, st.full_name AS staff_name, s.title service, ca.collaborative_service_id' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->leftJoin( 'Service', 's', 's.id = a.service_id' )
            ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
            ->where( 'ca.collaborative_token', $collaborative_token )
            ->fetchArray();
        $service_name = '';
        if ( $appointments ) {
            $service_name = BooklyLib\Entities\Service::find( $appointments[0]['collaborative_service_id'] )->getTitle();
        }

        wp_send_json_success( compact( 'service_name', 'appointments' ) );
    }
}