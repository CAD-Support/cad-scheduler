<?php
namespace BooklyCollaborativeServices\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Service;
use Bookly\Lib\Entities\Staff;
use Bookly\Lib\Entities\StaffService;
use Bookly\Lib\Utils;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareServices( $result )
    {
        // Add collaborative services.
        $query = BooklyLib\Proxy\Shared::prepareCaSeStQuery(
            Service::query( 's' )
                ->leftJoin( 'SubService', 'sub', 'sub.service_id = s.id' )
                ->leftJoin( 'StaffService', 'ss', 'ss.service_id = sub.sub_service_id' )
                ->where( 's.type', Service::TYPE_COLLABORATIVE )
        );
        $query->groupBy( 's.id' );
        $services = $query->find();

        /** @var Service $service */
        foreach ( $services as $service ) {
            $sub_services = $service->getSubServices();
            if ( ! empty ( $sub_services ) ) {
                // Find min and max capacity.
                $max_capacity = PHP_INT_MAX;
                $min_capacity = 1;
                $has_extras = 0;
                $service_time_requirements = array();
                foreach ( $sub_services as $sub_service ) {
                    if ( BooklyLib\Config::groupBookingActive() ) {
                        $res = StaffService::query()
                            ->select( 'MAX(capacity_max) AS max_capacity, MIN(capacity_min) AS min_capacity' )
                            ->where( 'service_id', $sub_service->getId() )
                            ->fetchRow();
                        if ( $res ) {
                            if ( $max_capacity > $res['max_capacity'] ) {
                                $max_capacity = $res['max_capacity'];
                            }
                            if ( $min_capacity < $res['min_capacity'] ) {
                                $min_capacity = $res['min_capacity'];
                            }
                        }
                    } else {
                        $max_capacity = 1;
                    }
                    if ( $has_extras == 0 ) {
                        $has_extras = (int) BooklyLib\Proxy\ServiceExtras::findByServiceId( $sub_service->getId() );
                    }
                    $service_time_requirements[ $sub_service->getTimeRequirements() ] = true;
                }
                $duration = 0;
                $sub_service_ids = array();
                foreach ( $sub_services as $sub_service ) {
                    if ( ! in_array( $sub_service->getId(), $sub_service_ids ) ) {
                        $sub_service_ids[] = $sub_service->getId();
                    }
                    $duration = max( $duration, $sub_service->getDuration() );
                }

                $min_time_prior_booking = BooklyLib\Slots\DatePoint::now()->modify( BooklyLib\Proxy\Pro::getMinimumTimePriorBooking( $service->getId() ) )->toClientTz();

                $time_requirements = 'required';
                if ( BooklyLib\Config::tasksActive() ) {
                    $time_requirements = array_key_exists( 'required', $service_time_requirements )
                        ? 'required'
                        : ( array_key_exists( 'optional', $service_time_requirements ) ? 'optional' : 'off' );
                }

                $result['services'][ $service->getId() ] = array(
                    'id' => (int) $service->getId(),
                    'category_id' => (int) $service->getCategoryId() ?: -1,
                    'name' => $service->getTranslatedTitle(),
                    'duration' => Utils\DateTime::secondsToInterval( $duration ),
                    'min_capacity' => (int) $min_capacity,
                    'max_capacity' => (int) $max_capacity,
                    'has_extras' => $has_extras,
                    'img' => $service->getImageUrl(),
                    'info' => BooklyLib\Config::getServiceInfoCodes( array(
                        'id' => $service->getId(),
                        'title' => $service->getTranslatedTitle(),
                        'info' => $service->getTranslatedInfo(),
                        'attachment_id' => $service->getAttachmentId(),
                        'price' => $service->getPrice(),
                        'duration' => $duration,
                    ) ),
                    'deposit' => $service->getDeposit(),
                    'pos' => (int) $service->getPosition(),
                    'type' => Service::TYPE_COLLABORATIVE,
                    'recurrence_enabled' => (int) $service->getRecurrenceEnabled(),
                    'recurrence_frequencies' => $service->getRecurrenceFrequencies(),
                    'service_info' => $service->getTranslatedInfo(),
                    'sub_service_ids' => $sub_service_ids,
                    'price' => $service->getPrice(),
                    'min_time_prior_booking' => array( (int) $min_time_prior_booking->format( 'Y' ), (int) $min_time_prior_booking->format( 'n' ) - 1, (int) $min_time_prior_booking->format( 'j' ), ),
                    'time_requirements' => $time_requirements,
                    'tags' => json_decode( $service->getTags() ?: '[]', true ),
                );
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCaSeSt( $result )
    {
        /** @var array $service */
        foreach ( $result['services'] as $service ) {
            if ( $service['type'] === Service::TYPE_COLLABORATIVE && $service['sub_service_ids'] ) {
                // Add service to staff.
                $query = Staff::query( 'st' )
                    ->select( 'st.id, st.full_name, st.position, ss.service_id, ss.capacity_min, ss.capacity_max, ss.price, s.units_min, s.units_max' )
                    ->where( 'ss.service_id', $service['sub_service_ids'][0] )
                    ->innerJoin( 'StaffService', 'ss', 'ss.staff_id = st.id AND st.visibility = "public"' )
                    ->leftJoin( 'Service', 's', 's.id = ss.service_id' );
                $query = BooklyLib\Proxy\Shared::prepareStaffServiceQuery( $query );
                if ( ! BooklyLib\Proxy\Locations::servicesPerLocationAllowed() ) {
                    $query->where( 'ss.location_id', null );
                }

                foreach ( $query->fetchArray() as $staff_service ) {
                    if ( ! isset ( $result['staff'][ $staff_service['id'] ] ) ) {
                        $staff = Staff::find( $staff_service['id'] );

                        $result['staff'][ $staff_service['id'] ] = array(
                            'id' => (int) $staff_service['id'],
                            'name' => $staff->getTranslatedName(),
                            'services' => array(),
                            'pos' => (int) $staff->getPosition(),
                            'img' => $staff->getImageUrl(),
                        );
                    }
                    $location_data = array(
                        'min_capacity' => $service['min_capacity'],
                        'max_capacity' => $service['max_capacity'],
                        'price' => Utils\Price::format( $service['price'] ),
                    );
                    $staff_service['duration'] = $service['duration'];
                    $location_data = BooklyLib\Proxy\Shared::prepareCategoryServiceStaffLocation( $location_data, $staff_service );
                    foreach ( BooklyLib\Proxy\Locations::prepareLocationsForCombinedServices( array( 0 ), $service['sub_service_ids'] ) as $location_id ) {
                        $result['staff'][ $staff_service['id'] ]['services'][ $service['id'] ]['locations'][ $location_id ] = $location_data;
                    }
                }
            }
        }

        return $result;
    }
}