<?php
namespace BooklyPro\Frontend\Modules\ModernBookingForm;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib as ProLib;
use Bookly\Lib\Entities;
use Bookly\Lib\Base\Gateway;
use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\Request;
use BooklyPro\Lib\Utils\Common;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    public static function modernBookingFormGetServices()
    {
        $list = array();
        $filters = self::parameter( 'filters' );
        $date = date_create( self::parameter( 'date' ) );
        if ( ! isset( $filters['locations'] ) ) {
            $filters['locations'] = array( 0 );
        }
        foreach ( $filters['services'] as $service_id ) {
            foreach ( $filters['locations'] as $location_id ) {
                $any_index = null;
                $staff_count = 0;
                if ( in_array( self::parameter( 'show_services_mode' ), array( 'all', 'only_any' ) ) && count( $filters['staff'] ) > 1 && self::hasSlots( $date, $service_id, $location_id, $filters['staff'] ) ) {
                    $list[] = array( 'service_id' => $service_id, 'staff_id' => $filters['staff'], 'location_id' => $location_id );
                    $any_index = count( $list ) - 1;
                }
                if ( self::parameter( 'show_services_mode' ) !== 'only_any' || count( $filters['staff'] ) === 1 ) {
                    foreach ( $filters['staff'] as $staff_id ) {
                        if ( self::hasSlots( $date, $service_id, $location_id, array( $staff_id ) ) ) {
                            $list[] = compact( 'service_id', 'staff_id', 'location_id' );
                            $staff_count++;
                        }
                    }
                }
                if ( in_array( self::parameter( 'show_services_mode' ), array( 'all', 'only_any' ) ) && count( $filters['staff'] ) > 1 && $staff_count === 1 && $any_index !== null ) {
                    array_splice( $list, $any_index, 1 );
                }
            }
        }

        wp_send_json_success( $list );
    }

    protected static function hasSlots( $date, $service_id, $location_id, $staff_ids )
    {
        $service = BooklyLib\Entities\Service::find( $service_id );
        if ( $service ) {
            $nop = 1;
            if ( BooklyLib\Config::groupBookingActive() ) {
                if ( count( $staff_ids ) > 1 ) {
                    $staff_nop = Entities\StaffService::query()
                        ->whereIn( 'staff_id', $staff_ids )
                        ->where( 'service_id', $service_id )
                        ->fetchVar( 'MIN(capacity_min)' );
                    $nop = $staff_nop ? (int) $staff_nop : $service->getCapacityMin();
                } elseif ( count( $staff_ids ) == 1 ) {
                    $staff_service = new Entities\StaffService();
                    if ( $staff_service->loadBy( array( 'service_id' => $service_id, 'staff_id' => $staff_ids[0], 'location_id' => null ) ) ) {
                        $nop = $staff_service->getCapacityMin();
                    }
                }
            }
            $chain_item = new BooklyLib\ChainItem();
            $chain_item
                ->setStaffIds( $staff_ids )
                ->setServiceId( $service_id )
                ->setNumberOfPersons( $nop )
                ->setQuantity( 1 )
                ->setLocationId( $location_id )
                ->setUnits( $service->getUnitsMin() )
                ->setExtras( array() );

            $chain = new BooklyLib\Chain();
            $chain->add( $chain_item );
            $params = array( 'every' => 1, 'full_day' => true );
            if ( BooklyLib\Config::useClientTimeZone() ) {
                $params['time_zone'] = self::parameter( 'time_zone' ) ?: null;
                $params['time_zone_offset'] = self::parameter( 'time_zone_offset' ) ?: null;
            }
            if ( self::parameter( 'show_blocked_slots' ) ) {
                $params['show_blocked_slots'] = true;
            }
            $scheduler = new BooklyLib\Scheduler( $chain, $date->format( 'Y-m-d 00:00' ), $date->format( 'Y-m-d' ), 'daily', $params, array(), BooklyLib\Config::waitingListActive() );
            $schedule = $scheduler->scheduleForFrontend( 1 );
            if ( isset( $schedule[0]['options'] ) && count( $schedule[0]['options'] ) ) {
                return true;
            }
        }

        return false;
    }

    public static function modernBookingFormGetSlots()
    {
        $staff_ids = array();
        $location_ids = array();
        $date = self::parameter( 'date' );
        $chain = new BooklyLib\Chain();
        foreach ( self::parameter( 'chain' ) as $item ) {
            $service_id = $item['service_id'];
            if ( is_array( $item['staff_id'] ) ) {
                $chain_staffs = $item['staff_id'];
                foreach ( $item['staff_id'] as $staff ) {
                    $staff_ids[] = $staff;
                }
            } else {
                $chain_staffs = array( $item['staff_id'] );
                $staff_ids[] = $item['staff_id'];
            }
            $location_id = $item['location_id'];
            if ( ! in_array( $location_id, $location_ids, true ) ) {
                $location_ids[] = $location_id;
            }
            $nop = isset( $item['nop'] ) ? $item['nop'] : 1;
            $units = isset( $item['units'] ) ? $item['units'] : 1;
            $extras = isset( $item['extras'] ) ? $item['extras'] : array();
            foreach ( array_keys( $extras, 0, false ) as $key ) {
                unset( $extras[ $key ] );
            }

            $chain_item = new BooklyLib\ChainItem();
            $chain_item
                ->setStaffIds( $chain_staffs )
                ->setServiceId( $service_id )
                ->setNumberOfPersons( $nop )
                ->setQuantity( 1 )
                ->setLocationId( $location_id )
                ->setUnits( $units )
                ->setExtras( $extras );

            $chain->add( $chain_item );
        }

        $params = array(
            'every' => 1,
            'with_nop' => true,
            'full_day' => true
        );

        $customer = self::parameter( 'customer' );
        $use_time_zone = false;
        if ( array_key_exists( 'time_zone', $customer ) && array_key_exists( 'time_zone_offset', $customer ) && ! $customer['time_zone_offset'] && $customer['time_zone'] ) {
            $time_zone = $customer['time_zone'];
            $time_zone_offset = null;
            if ( preg_match( '/^UTC[+-]/', $time_zone ) ) {
                $offset = preg_replace( '/UTC\+?/', '', $time_zone );
                $time_zone = null;
                $time_zone_offset = -$offset * 60;
            }
            $use_time_zone = true;
            $params['time_zone'] = $time_zone;
            $params['time_zone_offset'] = $time_zone_offset;
        } elseif ( BooklyLib\Config::useClientTimeZone() && ( isset( $customer['time_zone_offset'] ) || isset( $customer['time_zone'] ) ) ) {
            $time_zone_offset = isset( $customer['time_zone_offset'] ) && $customer['time_zone_offset'] !== '' ? $customer['time_zone_offset'] : null;
            $time_zone = isset( $customer['time_zone'] ) ? $customer['time_zone'] : null;
            $use_time_zone = true;
            $params['time_zone'] = $time_zone;
            $params['time_zone_offset'] = $time_zone_offset;
        }

        $request = new Request();
        $userData = $request->getUserData();
        $params['userData'] = $userData;

        if ( self::parameter( 'recurring_schedule' ) ) {
            $type = self::parameter( 'recurring_type' );
            switch ( $type ) {
                case 'daily':
                    $params['every'] = self::parameter( 'recurring_interval' );
                    break;
                case 'weekly':
                case 'biweekly':
                    $params['on'] = self::parameter( 'recurring_week_days' );
                    break;
                case 'monthly':
                    $on = self::parameter( 'recurring_month_type' );
                    $params['on'] = $on;
                    if ( $on === 'day' ) {
                        $params['day'] = self::parameter( 'recurring_month_days' );
                    } else {
                        $params['weekday'] = self::parameter( 'recurring_month_week' );
                    }
                    break;
            }
            if ( isset( $time_zone ) ) {
                $datetime = BooklyLib\Slots\DatePoint::fromStrInTz( $date . ' ' . self::parameter( 'recurring_time' ), $time_zone )->toWpTz()->format( 'Y-m-d H:i:s' );
            } else {
                $datetime = $date . ' ' . self::parameter( 'recurring_time' );
            }
            $params['full_day'] = false;
            $scheduler = new BooklyLib\Scheduler( $chain, $datetime, date_create( self::parameter( 'recurring_until' ) )->format( 'Y-m-d' ), $type, $params, array(), false );
            $schedule = $scheduler->scheduleForFrontend();
        } else {
            if ( self::parameter( 'show_blocked_slots' ) ) {
                $params['show_blocked_slots'] = true;
            }
            $scheduler = new BooklyLib\Scheduler( $chain, date_create( $date )->format( 'Y-m-d 00:00' ), date_create( $date )->format( 'Y-m-d' ), 'daily', $params, array(), ( ! self::hasParameter( 'waiting_list' ) || self::parameter( 'waiting_list' ) ) && BooklyLib\Config::waitingListActive() );
            $schedule = $scheduler->scheduleForFrontend( 1 );
            if ( count( $schedule ) > 1 ) {
                foreach ( array_keys( $schedule ) as $key ) {
                    if ( $schedule[ $key ]['date'] !== $date ) {
                        unset( $schedule[ $key ] );
                    }
                }
                $schedule = array_values( $schedule );
            }
        }

        foreach ( $schedule as &$_schedule ) {
            if ( isset( $_schedule['options'] ) ) {
                foreach ( $_schedule['options'] as &$option ) {
                    $slots = json_decode( $option['value'], false );
                    $option['slots'] = array();
                    $slot_index = 0;
                    foreach ( self::parameter( 'chain' ) as $item ) {
                        $service_id = $item['service_id'];
                        $service = Entities\Service::find( $service_id );
                        $slots_list = array();
                        $slot = $slots[ $slot_index ];
                        if ( $service->withSubServices() ) {
                            $services_count = count( $service->getSubServices() );
                            for ( $i = 0; $i < $services_count; $i++ ) {
                                $slots_list[] = $slots[ $slot_index + $i ];
                            }
                        } else {
                            $services_count = 1;
                            $slots_list[] = $slot;
                        }

                        if ( $service->withSubServices() && get_option( 'bookly_combined_price_method' ) !== 'nested' ) {
                            $price = $service->getPrice();
                        } else {
                            $price = 0;
                            foreach ( $slots_list as $_slot ) {
                                list( $service_id, $staff_id, $date, $location_id ) = $_slot;
                                $time = substr( $date, 11 );
                                $staff_service = new Entities\StaffService();
                                $location_id = BooklyLib\Proxy\Locations::prepareStaffLocationId( $location_id, $staff_id ) ?: null;
                                $staff_service->loadBy( compact( 'staff_id', 'service_id', 'location_id' ) );
                                $price += BooklyLib\Proxy\SpecialHours::adjustPrice( $staff_service->getPrice(), $staff_id, $service_id, $location_id, $time, date( 'w', strtotime( $date ) ) + 1 );
                            }
                        }

                        $option['slots'][] = array(
                            'price' => $price,
                            'datetime' => isset( $_schedule['all_day_service_time'] ) ? BooklyLib\Utils\DateTime::formatDate( $slot[2] ) . ' ' . $_schedule['all_day_service_time'] : ( $use_time_zone ? BooklyLib\Utils\DateTime::formatDateTime( BooklyLib\Utils\DateTime::applyTimeZone( $slot[2], $time_zone, $time_zone_offset ) ) : BooklyLib\Utils\DateTime::formatDateTime( $slot[2] ) ),
                            'slot' => $slots_list
                        );
                        $slot_index += $services_count;
                    }
                }
            }
        }
        unset ( $_schedule, $_day );
        $result = array( 'schedule' => $schedule );
        if ( ! self::parameter( 'recurring_schedule' ) && BooklyLib\Config::recurringAppointmentsActive() ) {
            $days = array();
            $staff_timezones = array();
            foreach ( Entities\Staff::query( 'st' )->select( 'id, time_zone' )->whereIn( 'id', $staff_ids )->fetchArray() as $staff ) {
                $staff_timezones[ $staff['id'] ] = $staff['time_zone'];
            }
            foreach ( self::_staffWeeklySchedule( $staff_ids, $staff_timezones ) as $_day => $_schedule ) {
                $start = $_schedule['start_time']->toClientTz();
                $end = $_schedule['end_time']->toClientTz();
                if ( $start->value() < 0 ) {
                    // Previous day
                    $_day = $_day === 1 ? 7 : $_day - 1;
                    $days[ $_day ] = $_day;
                }
                if ( $start->value() < HOUR_IN_SECONDS * 24 && $end->value() > 0 ) {
                    // Current day
                    $days[ $_day ] = $_day;
                }
                if ( $end->value() > HOUR_IN_SECONDS * 24 ) {
                    // Next day
                    $_day = $_day === 7 ? 1 : $_day + 1;
                    $days[ $_day ] = $_day;
                }
            }

            /** @var \WP_Locale $wp_locale */
            global $wp_locale;

            $weekdays = self::_getWeekDays();
            $weekday_abbrev = array_values( $wp_locale->weekday_abbrev );
            $result['days'] = array();
            foreach ( $weekdays as $day_index => $day ) {
                if ( in_array( $day_index + 1, $days, true ) ) {
                    $result['days'][] = array(
                        'value' => $day,
                        'label' => $weekday_abbrev[ $day_index ],
                    );
                }
            }
            $result['weekdays'] = $weekdays;
        }

        wp_send_json_success( $result );
    }

    public static function modernBookingFormSave()
    {
        $request = new Lib\Request();
        if ( $request->isValid() ) {
            try {
                wp_send_json_success( $request->processPayment() );
            } catch ( \Exception $e ) {
            }
        }

        wp_send_json_error( $request->getError() );
    }

    /**
     * When payment window closed by client
     * @return void
     */
    public static function retrieveOrderStatus()
    {
        wp_send_json_success( self::retrieveOrderResult() );
    }

    /**
     * Endpoint for payment systems window.
     *
     * @return void
     */
    public static function checkoutResponse()
    {
        $request = \Bookly\Frontend\Modules\Payment\Request::getInstance();
        try {
            $gateway = $request->getGateway();
            if ( $gateway instanceof BooklyLib\Payment\NullGateway ) {
                // Get here after closing payment modal window
                // NullGateway means that until then the webhook
                // that caused $gateway->failed() worked and there is no trace of the payment system left
                // hence we send status = Gateway::STATUS_FAILED
                $status = Gateway::STATUS_FAILED;
                $data = array(
                    'status' => $status,
                    'data' => Lib\PaymentFlow::getBookingResultFromOrder( $status, $request->get( 'bookly_order' ) ),
                );
            } else {
                $data = self::retrieveOrderResult();
            }
        } catch ( \Exception $e ) {
            $data = array(
                'status' => Gateway::STATUS_FAILED,
                'data' => array(
                    'bookly_order' => $request->get( 'bookly_order' ),
                    'info' => $e->getMessage(),
                ),
            );
        }
        if ( $request->getGateway()->getPayment()->getParentId() ) {
            print '<script>window.opener.BooklyCheckoutForm.setBookingResult( \'' . $data['status'] . '\', ' . json_encode( $data['data'] ) . ' );</script>';
        } else {
            print '<script>window.opener.BooklyModernBookingForm.setBookingResult( \'' . $data['status'] . '\', ' . json_encode( $data['data'] ) . ' );</script>';
        }
        exit;
    }

    /**
     * Get staff schedule for modern form calendar
     *
     * @return void
     */
    public static function modernBookingFormGetCalendarSchedule()
    {
        $holidays = array();
        $parsed_month = array();
        $filters = self::parameter( 'filters' );

        $month = (int) self::parameter( 'month' );
        $year = (int) self::parameter( 'year' );


        $last_date = date_create()->modify( ( BooklyLib\Config::getMaximumAvailableDaysForBooking() - 1 ) . ' days' );
        $last_month = $last_date->format( 'n' ) - 1;
        $last_year = (int) $last_date->format( 'Y' );

        $service_ids = isset( $filters['services'] ) ? $filters['services'] : array();
        $staff_ids = isset( $filters['staff'] ) ? $filters['staff'] : array();

        // Minimum time prior booking
        $min_time_prior_booking = null;
        if ( $service_ids ) {
            foreach ( $service_ids as $service_id ) {
                $service_min_time = ProLib\Config::getMinimumTimePriorBooking( $service_id );
                $min_time_prior_booking = $min_time_prior_booking === null ? $service_min_time : min( $min_time_prior_booking, $service_min_time );
            }
        } else {
            $min_time_prior_booking = ProLib\Config::getMinimumTimePriorBooking();
        }

        $first_month = $month;
        $first_year = $year;
        do {
            $parsed_month[] = ( $month - 1 ) . '-' . $year;

            $has_schedule = self::_getSchedule( $month, $year, $min_time_prior_booking, $staff_ids, $service_ids, $holidays );

            $year += $month > 11 ? 1 : 0;
            $month = ( $month % 12 ) + 1;
        } while ( ! $has_schedule && ( $year < $last_year || ( $year === $last_year && $month <= $last_month ) ) );

        // Add holidays to min time prior booking days
        if ( $min_time_prior_booking ) {
            $start_date = date_create( $first_year . '-' . ( $first_month - 1 ) . '-1' )->modify( '-7 days' );
            $end_date = date_create( $year . '-' . ( $month - 1 ) . '-1' )->modify( '1 month 7 days' )->modify( $min_time_prior_booking . ' seconds' );

            $min_date = BooklyLib\Slots\DatePoint::now()->toTz( 'UTC' )->modify( $min_time_prior_booking )->modify( 'midnight' )->value();
            do {
                if ( $start_date < $min_date ) {
                    $formatted_date = $start_date->format( 'Y-m-d' );
                    if ( ! in_array( $formatted_date, $holidays, true ) ) {
                        $holidays[] = $formatted_date;
                    }
                }
                $start_date->modify( '1 day' );
            } while ( $start_date < $end_date );
        }

        wp_send_json_success( array( 'parsed_months' => $parsed_month, 'holidays' => $holidays ) );
    }

    private static function _getSchedule( $month, $year, $min_time_prior_booking, $staff_ids, $service_ids, &$holidays )
    {
        $staff_holidays = array();
        $service_holidays = array();

        $start_date = date_create( $year . '-' . $month . '-1' )->modify( '-7 days' );
        $end_date = date_create( $year . '-' . $month . '-1' )->modify( '1 month 8 days' );

        if ( $min_time_prior_booking ) {
            $end_date->modify( $min_time_prior_booking . ' seconds' );
        }

        if ( self::parameter( 'available_dates' ) === 'with_slots' ) {
            $current_date = clone( $start_date );
            do {
                $service_holidays[] = $current_date->format( 'Y-m-d' );
                $current_date->modify( '1 day' );
            } while ( $current_date < $end_date );
            foreach ( $service_ids as $service_id ) {
                $service = BooklyLib\Entities\Service::find( $service_id );
                $nop = 1;
                if ( BooklyLib\Config::groupBookingActive() ) {
                    if ( count( $staff_ids ) > 1 ) {
                        $nop = $service->getCapacityMin();
                    } elseif ( count( $staff_ids ) == 1 ) {
                        $staff_service = new Entities\StaffService();
                        if ( $staff_service->loadBy( array( 'service_id' => $service_id, 'staff_id' => $staff_ids[0], 'location_id' => null ) ) ) {
                            $nop = $staff_service->getCapacityMin();
                        }
                    }
                }

                $chain_item = new BooklyLib\ChainItem();
                $chain_item
                    ->setStaffIds( $staff_ids )
                    ->setServiceId( $service_id )
                    ->setNumberOfPersons( $nop )
                    ->setQuantity( 1 )
                    ->setLocationId( null )
                    ->setUnits( $service->getUnitsMin() )
                    ->setExtras( array() );

                $chain = new BooklyLib\Chain();
                $chain->add( $chain_item );

                $userData = new BooklyLib\UserBookingData( null );
                if ( BooklyLib\Config::useClientTimeZone() ) {
                    $userData
                        ->setTimeZone( self::parameter( 'time_zone' ) ?: null )
                        ->setTimeZoneOffset( self::parameter( 'time_zone_offset' ) ?: null )
                        ->applyTimeZone();
                }
                $userData->resetChain();
                $userData->chain = $chain;
                $userData->setDays( array( 1, 2, 3, 4, 5, 6, 7 ) );
                $finder_end_date = new BooklyLib\Slots\DatePoint( $end_date );
                $finder = new BooklyLib\Slots\Finder( $userData, null, function( BooklyLib\Slots\DatePoint $client_dp, $groups_count, $slots_count ) use ( $finder_end_date ) {
                    return (int) $client_dp->gte( $finder_end_date );
                }, null, array(), false, false );
                $finder->prepare();
                $finder->client_start_dp = new BooklyLib\Slots\DatePoint( $start_date );
                $finder->client_end_dp = $finder_end_date;
                $finder->load();
                $service_holidays = array_diff( $service_holidays, array_keys( $finder->getSlots() ), array( $end_date->format( 'Y-m-d' ) ) );
            }
        } else {
            if ( $staff_ids ) {
                // Holidays
                $query = Entities\Holiday::query( 'h' )
                    ->select( 'DISTINCT(DATE_FORMAT(h.date, "%%m-%%d")) AS short_date' )
                    ->whereIn( 'h.staff_id', $staff_ids )
                    ->whereRaw( 'h.repeat_event = 1 OR (h.date >= %s AND h.date <= %s)', array( $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ) ) )
                    ->groupBy( 'short_date' )
                    ->havingRaw( 'COUNT(DISTINCT(staff_id)) >= %d', array( count( $staff_ids ) ) );
                $staff_holidays = $query->fetchArray();

                $staff_holidays = array_map( function( $h ) { return $h['short_date']; }, $staff_holidays );

                $staff_timezones = array();
                foreach ( Entities\Staff::query( 'st' )->select( 'id, time_zone' )->whereIn( 'id', $staff_ids )->fetchArray() as $staff ) {
                    $staff_timezones[ $staff['id'] ] = $staff['time_zone'];
                }

                $wp_tz_offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

                // Calculate weekly schedule
                $weekly_schedule = self::_staffWeeklySchedule( $staff_ids, $staff_timezones );

                // Special days
                $special_days = array();
                foreach ( BooklyLib\Proxy\SpecialDays::getSchedule( $staff_ids, $start_date, $end_date ) ?: array() as $special_day ) {
                    $start = BooklyLib\Slots\TimePoint::fromStr( $special_day['start_time'] );
                    $end = BooklyLib\Slots\TimePoint::fromStr( $special_day['end_time'] );
                    if ( isset( $staff_timezones[ $special_day['staff_id'] ] ) ) {
                        $staff_tz_offset = BooklyLib\Utils\DateTime::timeZoneOffset( $staff_timezones[ $special_day['staff_id'] ] );
                        $start = $start->toTz( $staff_tz_offset, $wp_tz_offset );
                        $end = $end->toTz( $staff_tz_offset, $wp_tz_offset );
                    }
                    if ( ! isset( $special_days[ $special_day['date'] ]['start_time'] ) || $start->lt( $special_days[ $special_day['date'] ]['start_time'] ) ) {
                        $special_days[ $special_day['date'] ]['start_time'] = $start;
                    }
                    if ( ! isset( $special_days[ $special_day['date'] ]['end_time'] ) || $end->gt( $special_days[ $special_day['date'] ]['end_time'] ) ) {
                        $special_days[ $special_day['date'] ]['end_time'] = $end;
                    }
                }

                $current_date = clone( $start_date );
                $working_days = array();
                do {
                    $month_day = $current_date->format( 'm-d' );
                    $day = $current_date->format( 'Y-m-d' );
                    if ( ! in_array( $month_day, $staff_holidays, true ) ) {
                        $weekday = 1 + (int) $current_date->format( 'w' );
                        if ( isset( $weekly_schedule[ $weekday ] ) ) {
                            $working_days = array_merge( $working_days, self::_prepareDates( $current_date, $weekly_schedule[ $weekday ]['start_time'], $weekly_schedule[ $weekday ]['end_time'] ) );
                        }
                    }
                    if ( isset( $special_days[ $day ] ) ) {
                        $working_days = array_merge( $working_days, self::_prepareDates( $current_date, $special_days[ $day ]['start_time'], $special_days[ $day ]['end_time'] ) );
                    }
                    $current_date->modify( '1 day' );
                } while ( $current_date < $end_date );

                $current_date = clone( $start_date );
                do {
                    $formatted_date = $current_date->format( 'Y-m-d' );
                    if ( ! in_array( $formatted_date, $working_days, true ) ) {
                        $staff_holidays[] = $formatted_date;
                    }
                    $current_date->modify( '1 day' );
                } while ( $current_date < $end_date );
            }

            if ( $service_ids && BooklyLib\Config::serviceScheduleActive() ) {
                // Calculate weekly schedule
                $weekly_schedule = BooklyLib\Proxy\ServiceSchedule::getWeeklySchedule( $service_ids );

                // Special days
                $special_days = array();
                foreach ( BooklyLib\Proxy\SpecialDays::getServiceSchedule( $service_ids, $start_date, $end_date ) ?: array() as $special_day ) {
                    $start = BooklyLib\Slots\TimePoint::fromStr( $special_day['start_time'] );
                    $end = BooklyLib\Slots\TimePoint::fromStr( $special_day['end_time'] );
                    if ( ! isset( $special_days[ $special_day['date'] ]['start_time'] ) || $start->lt( $special_days[ $special_day['date'] ]['start_time'] ) ) {
                        $special_days[ $special_day['date'] ]['start_time'] = $start;
                    }
                    if ( ! isset( $special_days[ $special_day['date'] ]['end_time'] ) || $end->gt( $special_days[ $special_day['date'] ]['end_time'] ) ) {
                        $special_days[ $special_day['date'] ]['end_time'] = $end;
                    }
                }

                $current_date = clone( $start_date );
                $working_days = array();
                do {
                    $month_day = $current_date->format( 'm-d' );
                    $day = $current_date->format( 'Y-m-d' );
                    if ( ! in_array( $month_day, $service_holidays, true ) ) {
                        $weekday = 1 + (int) $current_date->format( 'w' );
                        if ( isset( $weekly_schedule[ $weekday ] ) ) {
                            $working_days = array_merge( $working_days, self::_prepareDates( $current_date, $weekly_schedule[ $weekday ]['start_time'], $weekly_schedule[ $weekday ]['end_time'] ) );
                        }
                    }
                    if ( isset( $special_days[ $day ] ) ) {
                        $working_days = array_merge( $working_days, self::_prepareDates( $current_date, $special_days[ $day ]['start_time'], $special_days[ $day ]['end_time'] ) );
                    }
                    $current_date->modify( '1 day' );
                } while ( $current_date < $end_date );

                $current_date = clone( $start_date );
                do {
                    $formatted_date = $current_date->format( 'Y-m-d' );
                    if ( ! in_array( $formatted_date, $working_days, true ) ) {
                        $service_holidays[] = $formatted_date;
                    }
                    $current_date->modify( '1 day' );
                } while ( $current_date < $end_date );
            }
        }

        $holidays = array_merge( $holidays, $staff_holidays, $service_holidays );

        // Check if schedules has working day
        $current_date = max( date_create( $year . '-' . $month . '-1' ), date_create()->modify( $min_time_prior_booking . ' seconds' )->modify( 'midnight' ) );
        $end_month_date = date_create( $year . '-' . $month . '-1' )->modify( 'last day of this month' );
        if ( $current_date < $end_month_date ) {
            do {
                $formatted_date = $current_date->format( 'Y-m-d' );
                if ( ! in_array( $formatted_date, $staff_holidays, true ) && ! in_array( $formatted_date, $service_holidays, true ) ) {
                    return true;
                }
                $current_date->modify( '1 day' );
            } while ( $current_date < $end_month_date );
        }

        return false;
    }

    private static function _staffWeeklySchedule( $staff_ids, $staff_timezones = array() )
    {
        $weekly_schedule = array();
        $res = Entities\StaffScheduleItem::query()
            ->select( 'r.day_index, r.start_time, r.end_time, r.staff_id' )
            ->whereIn( 'r.staff_id', $staff_ids )
            ->whereNot( 'r.start_time', null )
            ->groupBy( 'r.staff_id' )
            ->groupBy( 'day_index' )
            ->fetchArray();

        $wp_tz_offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

        foreach ( $res as $row ) {
            $start = BooklyLib\Slots\TimePoint::fromStr( $row['start_time'] );
            $end = BooklyLib\Slots\TimePoint::fromStr( $row['end_time'] );
            if ( isset( $staff_timezones[ $row['staff_id'] ] ) ) {
                $staff_tz_offset = BooklyLib\Utils\DateTime::timeZoneOffset( $staff_timezones[ $row['staff_id'] ] );
                $start = $start->toTz( $staff_tz_offset, $wp_tz_offset );
                $end = $end->toTz( $staff_tz_offset, $wp_tz_offset );
            }
            if ( ! isset( $weekly_schedule[ $row['day_index'] ]['start_time'] ) || $start->lt( $weekly_schedule[ $row['day_index'] ]['start_time'] ) ) {
                $weekly_schedule[ $row['day_index'] ]['start_time'] = $start;
            }
            if ( ! isset( $weekly_schedule[ $row['day_index'] ]['end_time'] ) || $end->gt( $weekly_schedule[ $row['day_index'] ]['end_time'] ) ) {
                $weekly_schedule[ $row['day_index'] ]['end_time'] = $end;
            }
        }

        return $weekly_schedule;
    }

    /**
     * @param \DateTime $date
     * @param BooklyLib\Slots\TimePoint $start_time
     * @param BooklyLib\Slots\TimePoint $end_time
     * @return array
     */
    protected static function _prepareDates( $date, $start_time, $end_time )
    {
        // Convert to client time zone.
        $start = $start_time->toClientTz();
        $end = $end_time->toClientTz();

        $result = array();
        if ( $start->value() < 0 ) {
            $clone_date = clone( $date );
            $result[] = $clone_date->modify( '-1 day' )->format( 'Y-m-d' );
        }
        if ( $start->value() < HOUR_IN_SECONDS * 24 && $end->value() > 0 ) {
            $result[] = $date->format( 'Y-m-d' );
        }
        if ( $end->value() > HOUR_IN_SECONDS * 24 ) {
            $clone_date = clone( $date );
            $result[] = $clone_date->modify( '1 day' )->format( 'Y-m-d' );
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public static function _getWeekDays()
    {
        $start_of_week = get_option( 'start_of_week' );
        $weekdays = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' );

        // Sort days considering start_of_week;
        uksort( $weekdays, function( $a, $b ) use ( $start_of_week ) {
            $a -= $start_of_week;
            $b -= $start_of_week;
            if ( $a < 0 ) {
                $a += 7;
            }
            if ( $b < 0 ) {
                $b += 7;
            }

            return $a - $b;
        } );

        return $weekdays;
    }

    /**
     * @return array
     */
    protected static function retrieveOrderResult()
    {
        $request = \Bookly\Frontend\Modules\Payment\Request::getInstance();
        $gateway = $request->getGateway();
        if ( $request->get( 'bookly_event' ) === Gateway::EVENT_CANCEL ) {
            $status = Gateway::STATUS_FAILED;
            $gateway->fail();
        } else {
            $status = $gateway->retrieve();
        }

        return array(
            'status' => $status,
            'data' => Lib\PaymentFlow::getBookingResultFromOrder( $status, $request->get( 'bookly_order' ) ),
        );
    }

    /**
     * Override parent method to exclude actions from CSRF token verification.
     *
     * @param string $action
     * @return bool
     */
    protected static function csrfTokenValid( $action = null )
    {
        return true;
    }
}