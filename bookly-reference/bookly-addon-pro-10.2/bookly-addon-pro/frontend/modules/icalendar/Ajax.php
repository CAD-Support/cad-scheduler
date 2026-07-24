<?php
namespace BooklyPro\Frontend\Modules\Icalendar;

use Bookly\Lib as BooklyLib;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Staff iCalendar feed
     */
    public static function staffIcalendar()
    {
        /** @var BooklyLib\Entities\Staff $staff */
        $staff = BooklyLib\Entities\Staff::query()->where( 'icalendar_token', self::parameter( 'token' ) )->findOne();

        $ics = new BooklyLib\Utils\Ics\Feed();

        if ( $staff && $staff->getICalendar() ) {
            /** @var BooklyLib\Entities\Appointment[] $appointments */
            $appointments = BooklyLib\Entities\Appointment::query()
                ->where( 'staff_id', $staff->getId() )
                ->whereGte( 'start_date', date_create()->modify( -$staff->getICalendarDaysBefore() . 'days' )->format( 'Y-m-d' ) )
                ->whereLte( 'end_date', date_create()->modify( $staff->getICalendarDaysAfter() . 'days' )->format( 'Y-m-d' ) )
                ->find();

            $template = BooklyLib\Utils\Codes::getICSDescriptionTemplate( 'staff' );
            foreach ( $appointments as $appointment ) {
                if ( $appointment->getServiceId() === null ) {
                    $service_name = $appointment->getCustomServiceName();
                } else {
                    $service = BooklyLib\Entities\Service::find( $appointment->getServiceId() );
                    $service_name = $service->getTranslatedTitle();
                }

                $staff = BooklyLib\Entities\Staff::find( $appointment->getStaffId() );
                $codes = BooklyLib\Utils\Codes::getAppointmentCodes( $appointment );

                if ( empty( $codes['participants'] ) ) {
                    $codes += array( 'client_name' => '', 'client_phone' => '', 'status' => '' );
                } else {
                    // Merge first participant's codes (extras, custom_fields, coupon, etc.) as flat-level fallback.
                    // Appointment-level codes in $codes take priority (they're placed second in array_merge).
                    $codes = array_merge( $codes['participants'][0], $codes );
                    // Re-apply aggregated multi-participant values.
                    $codes['client_name'] = implode( ', ', array_column( $codes['participants'], 'client_name' ) );
                    $codes['client_phone'] = implode( ', ', array_column( $codes['participants'], 'client_phone' ) );
                    $codes['status'] = implode( ', ', array_column( $codes['participants'], 'status' ) );
                }

                $description = BooklyLib\Utils\Codes::replace( $template, $codes, false );
                $ics->addEvent( $appointment->getStartDate(), $appointment->getEndDate(), $service_name, $staff->getFullName(), $staff->getEmail(), $description, $appointment->getLocationId() );
            }
        }

        echo $ics->render();

        exit();
    }

    /**
     * Override parent method to exclude actions from CSRF token verification.
     *
     * @param string $action
     * @return bool
     */
    protected static function csrfTokenValid( $action = null )
    {
        $excluded_actions = array(
            'staffIcalendar',
        );

        return in_array( $action, $excluded_actions ) || parent::csrfTokenValid( $action );
    }
}