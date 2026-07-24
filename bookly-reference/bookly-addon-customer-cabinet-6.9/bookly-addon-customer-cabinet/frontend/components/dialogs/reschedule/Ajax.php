<?php
namespace BooklyCustomerCabinet\Frontend\Components\Dialogs\Reschedule;

use Bookly\Lib as BooklyLib;

class Ajax extends BooklyLib\Base\Ajax
{
    /** @var BooklyLib\Entities\Customer */
    protected static $customer;

    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'customer', );
    }

    /**
     * Get day schedule for "reschedule" button
     */
    public static function getDaySchedule()
    {
        $ca_id = self::parameter( 'ca_id' );

        $ca = BooklyLib\Entities\CustomerAppointment::find( $ca_id );
        if ( $ca->getCustomerId() == self::$customer->getId() ) {
            $durations = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                ->select( 'a.service_id, (UNIX_TIMESTAMP(a.end_date)-UNIX_TIMESTAMP(a.start_date)) AS duration' )
                ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' );

            $date = self::parameter( 'date' );
            if ( $ca->getCompoundToken() ) {
                $ignore_appointments = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                    ->where( 'ca.compound_token', $ca->getCompoundToken() )
                    ->fetchCol( 'appointment_id' );
                $service_id = $ca->getCompoundServiceId();
                /** @var BooklyLib\Entities\Appointment $appointment */
                $appointment = BooklyLib\Entities\Appointment::query( 'a' )
                    ->leftJoin( 'CustomerAppointment', 'ca', 'a.id = ca.appointment_id' )
                    ->where( 'ca.compound_token', $ca->getCompoundToken() )
                    ->findOne();
                $durations->where( 'ca.compound_token', $ca->getCompoundToken() );
            } elseif ( $ca->getCollaborativeToken() ) {
                $ignore_appointments = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                    ->where( 'ca.collaborative_token', $ca->getCollaborativeToken() )
                    ->fetchCol( 'appointment_id' );
                $service_id = $ca->getCollaborativeServiceId();
                /** @var BooklyLib\Entities\Appointment $appointment */
                $appointment = BooklyLib\Entities\Appointment::query( 'a' )
                    ->leftJoin( 'CustomerAppointment', 'ca', 'a.id = ca.appointment_id' )
                    ->where( 'ca.collaborative_token', $ca->getCollaborativeToken() )
                    ->findOne();
                $durations->where( 'ca.collaborative_token', $ca->getCollaborativeToken() );
            } else {
                $ignore_appointments = array( $ca->getAppointmentId() );
                $appointment = BooklyLib\Entities\Appointment::find( $ca->getAppointmentId() );
                $service_id = $appointment->getServiceId();
                $durations->where( 'ca.id', $ca->getId() );
            }
            if ( $service_id === null ) {
                $service_id = 0;
                // Custom service.
                $service = new BooklyLib\Entities\Service();
                $service->setDuration( strtotime( $appointment->getEndDate() ) - strtotime( $appointment->getStartDate() ) );
                BooklyLib\Entities\Service::putInCache( $service_id, $service );
            }
            if ( $appointment->getStaffAny() ) {
                $staff_ids = BooklyLib\Entities\StaffService::query( 'ss' )
                    ->leftJoin( 'Staff', 's', 's.id = ss.staff_id' )
                    ->where( 'ss.service_id', $appointment->getServiceId() )
                    ->where( 's.visibility', 'public' )
                    ->fetchCol( 'ss.staff_id' );
            } else {
                $staff_ids = array( $appointment->getStaffId() );
            }
            $location_id = $appointment->getLocationId();

            $chain_item = new BooklyLib\ChainItem();
            $chain_item
                ->setStaffIds( $staff_ids )
                ->setServiceId( $service_id )
                ->setNumberOfPersons( $ca->getNumberOfPersons() )
                ->setQuantity( 1 )
                ->setLocationId( $location_id )
                ->setUnits( $ca->getUnits() ?: 1 )
                ->setExtras( array() );

            $chain = new BooklyLib\Chain();
            $chain->add( $chain_item );

            $records = $durations->fetchArray();
            foreach ( $records as $record ) {
                $service = BooklyLib\Entities\Service::find( $record['service_id'] );
                if ( $service ) {
                    // Overwrite the cache duration to the appointment duration
                    $service->setDuration( $record['duration'] );
                }
            }

            $datetime = date_create( $date )->format( 'Y-m-d 00:00' );
            if ( $ca->getTimeZone() ) {
                // Interpret the picked date in the appointment's (client) time zone, not the
                // site time zone, otherwise the schedule is shifted by one day when they
                // differ (#388).
                $datetime = BooklyLib\Slots\DatePoint::fromStrInTz( $datetime, $ca->getTimeZone() )->toWpTz()->format( 'Y-m-d H:i:s' );
            }
            $scheduler = new BooklyLib\Scheduler( $chain, $datetime, date_create( $date )->format( 'Y-m-d' ), 'daily', array(
                'every' => 1,
                'time_zone' => $ca->getTimeZone(),
                'time_zone_offset' => $ca->getTimeZoneOffset(),
                'full_day' => true,
            ), array(), false, $ignore_appointments );
            $schedule = $scheduler->scheduleForFrontend( 1 );

            wp_send_json_success( $schedule );
        }
    }

    /**
     * Save rescheduled appointment with a new start date
     */
    public static function saveReschedule()
    {
        $response = array( 'success' => true, 'errors' => array() );

        $ca_id = self::parameter( 'ca_id' );

        $ca = BooklyLib\Entities\CustomerAppointment::find( $ca_id );
        $is_compound = false;
        $is_collaborative = false;
        if ( $ca->getCustomerId() == self::$customer->getId() ) {
            /** @var BooklyLib\Entities\CustomerAppointment[] $ca_list */
            if ( $ca->getCompoundToken() ) {
                $ca_list = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                    ->where( 'ca.compound_token', $ca->getCompoundToken() )
                    ->find();
                $is_compound = true;
                $compound_service = BooklyLib\Entities\Service::find( $ca->getCompoundServiceId() );
            } elseif ( $ca->getCollaborativeToken() ) {
                $ca_list = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                    ->where( 'ca.collaborative_token', $ca->getCollaborativeToken() )
                    ->find();
                $is_collaborative = true;
                $collaborative_service = BooklyLib\Entities\Service::find( $ca->getCollaborativeServiceId() );
            } else {
                $ca_list = array( $ca );
            }
            $slots = json_decode( self::parameter( 'slot' ), true );

            $appointment_ids = array();
            foreach ( $ca_list as $ca ) {
                $appointment_ids[] = $ca->getAppointmentId();
            }

            $reschedule_appointments = BooklyLib\Entities\Appointment::query()
                ->whereIn( 'id', $appointment_ids )
                ->indexBy( 'id' )
                ->fetchArray();

            foreach ( $reschedule_appointments as &$data ) {
                unset(
                    $data['id'],
                    $data['google_event_id'],
                    $data['google_event_etag'],
                    $data['outlook_event_id'],
                    $data['outlook_event_change_key'],
                    $data['outlook_event_series_id'],
                    $data['created_from'],
                    $data['online_meeting_id'],
                    $data['online_meeting_data']
                );
                if ( $data['online_meeting_provider'] === 'google_meet' ) {
                    unset( $data['online_meeting_provider'] );
                }
            }

            foreach ( $ca_list as $index => $ca ) {
                list( $service_id, $staff_id, $bound_start ) = $slots[ $index ];
                $appointment = BooklyLib\Entities\Appointment::find( $ca->getAppointmentId() );
                if ( $service_id === null ) {
                    // Custom service.
                    $custom_service = new BooklyLib\Entities\Service();
                    $custom_service->setDuration( strtotime( $appointment->getEndDate() ) - strtotime( $appointment->getStartDate() ) );
                    BooklyLib\Entities\Service::putInCache( null, $custom_service );
                }
                $duration = strtotime( $appointment->getEndDate() ) - strtotime( $appointment->getStartDate() ) + BooklyLib\Proxy\ServiceExtras::getTotalDuration( (array) json_decode( $ca->getExtras(), true ) );
                $bound_end = date( 'Y-m-d H:i:s', strtotime( $bound_start ) + $duration );

                if ( BooklyLib\Slots\DatePoint::now()->modify( BooklyLib\Proxy\Pro::getMinimumTimePriorBooking( $service_id ) )->toClientTz()->value()->getTimestamp() > strtotime( $bound_start ) ) {
                    // Check minimum time requirement prior to booking
                    $response['success'] = false;
                    $response['errors']['time_prior_booking'] = true;
                } elseif ( strtotime( $bound_start ) > current_time( 'timestamp' ) + BooklyLib\Config::getMaximumAvailableDaysForBooking() * DAY_IN_SECONDS ) {
                    // Check max available days for booking
                    $response['success'] = false;
                    $response['errors']['max_booking_date'] = true;
                } elseif ( BooklyLib\Config::packagesActive() && $ca->getPackageId() && date_create( $bound_start ) > BooklyLib\Proxy\Packages::getPackageExpireDate( $ca->getPackageId() ) ) {
                    // Check package lifetime
                    $response['success'] = false;
                    $response['errors']['max_booking_date'] = true;
                }
                // Search intersect appointments
                $query = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                    ->select( 'ss.capacity_max, SUM(ca.number_of_persons) AS total_number_of_persons,
                        DATE_SUB(a.start_date, INTERVAL COALESCE(s.padding_left,0) SECOND) AS bound_left,
                        DATE_ADD(a.end_date, INTERVAL (COALESCE(s.padding_right,0) + a.extras_duration) SECOND) AS bound_right'
                    )
                    ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
                    ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id' )
                    ->leftJoin( 'Service', 's', 's.id = a.service_id' )
                    ->where( 'a.staff_id', $staff_id )
                    ->whereIn( 'ca.status', BooklyLib\Proxy\CustomStatuses::prepareBusyStatuses( array(
                        BooklyLib\Entities\CustomerAppointment::STATUS_PENDING,
                        BooklyLib\Entities\CustomerAppointment::STATUS_APPROVED,
                        BooklyLib\Entities\CustomerAppointment::STATUS_WAITLISTED,
                    ) ) )
                    ->groupBy( 'a.service_id, a.start_date' )
                    ->whereNotIn( 'a.id', $appointment_ids )
                    ->havingRaw( '%s > bound_left AND bound_right > %s AND ( total_number_of_persons + %d ) > ss.capacity_max',
                        array( $bound_end, $bound_start, 1 ) )
                    ->limit( 1 );
                $rows = $query->execute( BooklyLib\Query::HYDRATE_NONE );
                if ( $rows != 0 ) {
                    // Exist intersect appointment, time not available.
                    $response['success'] = false;
                    $response['errors']['occupied'] = true;
                    break;
                }
            }

            if ( empty ( $response['errors'] ) ) {
                if ( $is_compound ) {
                    $new_token = BooklyLib\Utils\Common::generateToken( '\Bookly\Lib\Entities\CustomerAppointment', 'compound_token' );
                    $compound = BooklyLib\DataHolders\Booking\Compound::create( $compound_service )->setToken( $new_token );
                } elseif ( $is_collaborative ) {
                    $new_token = BooklyLib\Utils\Common::generateToken( '\Bookly\Lib\Entities\CustomerAppointment', 'collaborative_token' );
                    $collaborative = BooklyLib\DataHolders\Booking\Collaborative::create( $collaborative_service )->setToken( $new_token );
                }
                $ca_to_cancel = array();
                foreach ( $ca_list as $index => $ca ) {
                    list( $service_id, $staff_id, $bound_start ) = $slots[ $index ];
                    $duration = BooklyLib\Entities\Appointment::query()
                        ->where( 'id', $ca->getAppointmentId() )
                        ->fetchVar( '(UNIX_TIMESTAMP(end_date)-UNIX_TIMESTAMP(start_date))' );
                    $service = BooklyLib\Entities\Service::find( $service_id );
                    $bound_end = date( 'Y-m-d H:i:s', strtotime( $bound_start ) + $duration );
                    $appointment = BooklyLib\Entities\Appointment::query()
                        ->where( 'staff_id', $staff_id )
                        ->where( 'service_id', $service_id )
                        ->whereLte( 'start_date', $bound_start )
                        ->whereGte( 'end_date', $bound_end )
                        ->findOne();
                    if ( ! $appointment ) {
                        $appointment = new BooklyLib\Entities\Appointment( $reschedule_appointments[ $ca->getAppointmentId() ] );
                        $appointment
                            ->setStaffId( $staff_id )
                            ->setStartDate( $bound_start )
                            ->setEndDate( $bound_end )
                            ->save();
                    }

                    $ca_data = $ca->getFields();
                    unset( $ca_data['id'] );
                    $new_ca = new BooklyLib\Entities\CustomerAppointment( $ca_data );
                    $new_ca
                        ->setAppointment( $appointment )
                        ->setStatus( BooklyLib\Proxy\CustomerGroups::takeDefaultAppointmentStatus( BooklyLib\Config::getDefaultAppointmentStatus(), self::$customer->getGroupId() ) )
                        ->setCreatedAt( current_time( 'mysql' ) )
                        ->setToken( '' );
                    if ( $is_compound ) {
                        $new_ca->setCompoundToken( $new_token );
                    } elseif ( $is_collaborative ) {
                        $new_ca->setCollaborativeToken( $new_token );
                    }
                    $new_ca->save();

                    BooklyLib\Proxy\Shared::syncOnlineMeeting( array(), $appointment );
                    BooklyLib\Proxy\Pro::syncGoogleCalendarEvent( $appointment );
                    BooklyLib\Proxy\OutlookCalendar::syncEvent( $appointment );

                    $ca_to_cancel[] = $ca;
                    $item = BooklyLib\DataHolders\Booking\Simple::create( $new_ca )->setService( $service )->setAppointment( $appointment );
                    if ( $is_compound ) {
                        $item = $compound->addItem( $item );
                    } elseif ( $is_collaborative ) {
                        $item = $collaborative->addItem( $item );
                    }
                }
                foreach ( $ca_to_cancel as $ca ) {
                    if ( BooklyLib\Entities\CustomerAppointment::find( $ca->getId() ) ) {
                        $ca->cancel();
                    }
                }
                if ( isset( $item ) ) {
                    BooklyLib\Notifications\Booking\Sender::send( $item );
                }
            }
        }

        wp_send_json( $response );
    }

    /**
     * @inheritDoc
     */
    protected static function hasAccess( $action )
    {
        if ( parent::hasAccess( $action ) ) {
            self::$customer = BooklyLib\Entities\Customer::query()->where( 'wp_user_id', get_current_user_id() )->findOne();

            return self::$customer->isLoaded();
        }

        return false;
    }
}