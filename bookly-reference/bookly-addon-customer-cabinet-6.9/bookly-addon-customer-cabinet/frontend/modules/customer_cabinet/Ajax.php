<?php
namespace BooklyCustomerCabinet\Frontend\Modules\CustomerCabinet;

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
     * Get a list of appointments
     */
    public static function getAppointments()
    {
        $columns = BooklyLib\Utils\Tables::filterColumns( self::parameter( 'columns' ), BooklyLib\Utils\Tables::CUSTOMER_CABINET_APPOINTMENTS );
        $show_timezone = self::parameter( 'show_timezone' );
        $gmt_offset = get_option( 'gmt_offset' ) * 60;
        $order = self::parameter( 'order', array() );
        $postfix_any = sprintf( ' (%s)', get_option( 'bookly_l10n_option_employee' ) );
        $date_filter = self::parameter( 'date' );
        $service_filter = self::parameter( 'service' );
        $staff_filter = self::parameter( 'staff' );

        $query = BooklyLib\Entities\Appointment::query( 'a' )
            ->select(
                'ca.id AS ca_id,
                    s.category_id,
                    c.name AS category,
                    COALESCE(ca.compound_service_id,ca.collaborative_service_id,a.service_id) AS service_id,
                    a.staff_id,
                    a.location_id,
                    a.custom_service_name,
                    a.online_meeting_provider,
                    a.online_meeting_id,
                    a.online_meeting_data,
                    COALESCE(s.title, a.custom_service_name) AS service_title,
                    st.full_name AS staff_name,
                    a.staff_any,
                    ca.series_id,
                    ca.package_id,
                    ca.status,
                    ca.extras,
                    ca.compound_token,
                    ca.collaborative_token,
                    ca.number_of_persons,
                    ca.custom_fields,
                    ca.extras_multiply_nop,
                    ca.appointment_id,
                    ca.time_zone,
                    ca.time_zone_offset,
                    p.id AS payment_id,
                    COALESCE(p.total, IF (ca.compound_service_id IS NULL AND ca.collaborative_service_id IS NULL, COALESCE(ss.price, ss_no_location.price, a.custom_service_price), s.price)) AS price,
                    (TIMESTAMPDIFF(SECOND, MAX(a.start_date), MIN(a.end_date)) + a.extras_duration) AS duration,
                    a.start_date,
                    a.end_date,
                    s.start_time_info,
                    ca.token' )
            ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
            ->innerJoin( 'CustomerAppointment', 'ca', 'ca.appointment_id = a.id' )
            ->leftJoin( 'Service', 's', 's.id = COALESCE(ca.compound_service_id, ca.collaborative_service_id, a.service_id)' )
            ->leftJoin( 'Category', 'c', 'c.id = s.category_id' )
            ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id AND ss.location_id <=> a.location_id' )
            ->leftJoin( 'StaffService', 'ss_no_location', 'ss_no_location.staff_id = a.staff_id AND ss_no_location.service_id = a.service_id AND ss_no_location.location_id IS NULL' )
            ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
            ->where( 'ca.customer_id', self::$customer->getId() )
            ->groupBy( 'COALESCE(compound_token, collaborative_token, ca.id)' );

        BooklyLib\Proxy\Locations::prepareAppointmentsQuery( $query );

        $sub_query = BooklyLib\Proxy\Files::getSubQueryAttachmentExists();
        if ( ! $sub_query ) {
            $sub_query = '0';
        }
        $query->addSelect( '(' . $sub_query . ') AS attachment' );
        if ( $date_filter !== null ) {
            if ( $date_filter == 'any' ) {
                $query->whereNot( 'a.start_date', null );
            } elseif ( $date_filter == 'null' ) {
                $query->where( 'a.start_date', null );
            } else {
                list ( $start, $end ) = explode( ' - ', $date_filter, 2 );
                $end = date( 'Y-m-d', strtotime( $end ) + DAY_IN_SECONDS );
                $query->whereBetween( 'a.start_date', $start, $end );
            }
        }

        if ( $staff_filter !== null && $staff_filter !== '' ) {
            $query->where( 'a.staff_id', $staff_filter );
        }

        if ( $service_filter !== null && $service_filter !== '' ) {
            $query->where( 'a.service_id', $service_filter ?: null );
        }

        foreach ( $order as $sort_by ) {
            $query->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? BooklyLib\Query::ORDER_DESCENDING : BooklyLib\Query::ORDER_ASCENDING );
        }

        $total = $query->count( true );
        $query->limit( self::parameter( 'length' ) )->offset( self::parameter( 'start' ) );

        $data = array();
        $location_active = BooklyLib\Config::locationsActive();
        $busy_statuses = BooklyLib\Proxy\CustomStatuses::prepareBusyStatuses( array(
            BooklyLib\Entities\CustomerAppointment::STATUS_PENDING,
            BooklyLib\Entities\CustomerAppointment::STATUS_APPROVED,
        ) );
        $free_statuses_for_cancel = BooklyLib\Proxy\CustomStatuses::prepareFreeStatuses( array(
            BooklyLib\Entities\CustomerAppointment::STATUS_CANCELLED,
            BooklyLib\Entities\CustomerAppointment::STATUS_REJECTED,
            BooklyLib\Entities\CustomerAppointment::STATUS_DONE,
        ) );
        $free_statuses_for_reschedule = BooklyLib\Proxy\CustomStatuses::prepareFreeStatuses( array(
            BooklyLib\Entities\CustomerAppointment::STATUS_CANCELLED,
            BooklyLib\Entities\CustomerAppointment::STATUS_REJECTED,
            BooklyLib\Entities\CustomerAppointment::STATUS_WAITLISTED,
            BooklyLib\Entities\CustomerAppointment::STATUS_DONE,
        ) );
        foreach ( $query->fetchArray() as $row ) {
            // Appointment status.
            $row['appointment_status_text'] = BooklyLib\Entities\CustomerAppointment::statusToString( $row['status'] );
            // Custom fields
            $customer_appointment = new BooklyLib\Entities\CustomerAppointment();
            $customer_appointment->load( $row['ca_id'] );
            $custom_fields = array();
            $fields_data = BooklyLib\Proxy\CustomFields::getWhichHaveData() ?: array();
            $staff_name = BooklyLib\Entities\Staff::find( $row['staff_id'] )->getTranslatedName();
            $category_name = $row['category_id']
                ? BooklyLib\Entities\Category::find( $row['category_id'] )->getTranslatedName()
                : '';
            $location = $location_active && isset( $row['location_id'] )
                ? BooklyLib\Proxy\Locations::findById( $row['location_id'] )->getTranslatedName()
                : '';

            foreach ( $fields_data as $field_data ) {
                $custom_fields[ $field_data->id ] = '';
            }
            foreach ( BooklyLib\Proxy\CustomFields::getForCustomerAppointment( $customer_appointment, true ) ?: array() as $custom_field ) {
                $custom_fields[ $custom_field['id'] ] = $custom_field['value'];
            }
            $allow_cancel_time = current_time( 'timestamp' ) + (int) BooklyLib\Proxy\Pro::getMinimumTimePriorCancel( $row['service_id'] );

            $allow_cancel = 'blank';
            if ( ! in_array( $row['status'], $free_statuses_for_cancel ) ) {
                if ( in_array( $row['status'], $busy_statuses ) && $row['start_date'] === null ) {
                    $allow_cancel = 'allow';
                } else {
                    if ( $row['start_date'] > current_time( 'mysql' ) ) {
                        if ( $allow_cancel_time < strtotime( $row['start_date'] ) ) {
                            $allow_cancel = 'allow';
                        } else {
                            $allow_cancel = 'deny';
                        }
                    } else {
                        $allow_cancel = 'expired';
                    }
                }
            }
            $is_free_status = true;
            $allow_reschedule = 'blank';
            if ( ! in_array( $row['status'], $free_statuses_for_reschedule ) && $row['start_date'] !== null ) {
                $is_free_status = false;
                if ( $row['start_date'] > current_time( 'mysql' ) ) {
                    if ( $allow_cancel_time < strtotime( $row['start_date'] ) && BooklyLib\Proxy\SpecialHours::isNotInSpecialHour( date( 'H:i:s', strtotime( $row['start_date'] ) ), date( 'H:i:s', strtotime( $row['end_date'] ) ), $row['service_id'], $row['staff_id'], $row['location_id'], null ) ) {
                        $allow_reschedule = 'allow';
                    } else {
                        $allow_reschedule = 'deny';
                    }
                } else {
                    $allow_reschedule = 'expired';
                }
            }
            $price = null;
            if ( $row['series_id'] && ! $row['payment_id'] ) {
                $payment_query = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                    ->select( 'p.total' )
                    ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
                    ->where( 'series_id', $row['series_id'] )
                    ->whereNot( 'payment_id', null )
                    ->limit( 1 );
                if ( $payment = $payment_query->fetchCol( 'total' ) ) {
                    $price = BooklyLib\Utils\Price::format( reset( $payment ) );
                }
            }
            if ( $price === null ) {
                $price = $row['price'] !== null
                    ? BooklyLib\Utils\Price::format( $row['price'] + BooklyLib\Proxy\ServiceExtras::getTotalPrice( (array) json_decode( $row['extras'], true ), $row['number_of_persons'] ) )
                    : __( 'N/A', 'bookly' );
            }
            $appointment = new BooklyLib\Entities\Appointment();
            $appointment->setOnlineMeetingData( $row['online_meeting_data'] )
                ->setOnlineMeetingProvider( $row['online_meeting_provider'] )
                ->setOnlineMeetingId( $row['online_meeting_id'] );

            // Split date / time / timezone so the table can render the time (and timezone) as a
            // muted secondary line under the date, matching the backend Appointments screen.
            $applied_start = BooklyLib\Utils\DateTime::applyTimeZone( $row['start_date'], $row['time_zone'], $row['time_zone_offset'] );
            $start_time = null;
            $start_tz = null;
            if ( $row['start_date'] === null ) {
                $start_date = __( 'N/A', 'bookly' );
            } else {
                $start_date = BooklyLib\Utils\DateTime::formatDate( $applied_start );
                if ( $row['duration'] >= DAY_IN_SECONDS && $row['start_time_info'] !== '' ) {
                    $start_time = $row['start_time_info'];
                } else {
                    $start_time = BooklyLib\Utils\DateTime::formatTime( $applied_start );
                    if ( $show_timezone ) {
                        $time_zone_offset = $row['time_zone_offset'] === null ? $gmt_offset : -$row['time_zone_offset'];
                        $start_tz = $row['time_zone'] ?: 'UTC' . ( $time_zone_offset >= 0 ? '+' : '' ) . ( $time_zone_offset / 60 );
                    }
                }
            }

            $end_date = null;
            if ( BooklyLib\Config::packagesActive() && $row['package_id'] ) {
                $end_date = BooklyLib\Proxy\Packages::getPackageExpireDate( $row['package_id'] );
                $end_date = $end_date ? $end_date->format( 'Y-m-d' ) : null;
            }

            if ( $row['ca_id'] !== null ) {
                $extras = BooklyLib\Proxy\ServiceExtras::getCAInfo( $row['ca_id'], true ) ?: array();
                if ( $row['extras_multiply_nop'] && $row['number_of_persons'] > 1 ) {
                    foreach ( $extras as $index => $extra ) {
                        $extras[ $index ]['title'] = '<i class="far fa-user"></i>&nbsp;' . $row['number_of_persons'] . '&nbsp;&times;&nbsp;' . $extra['title'];
                    }
                }
            } else {
                $extras = array();
            }

            $data[] = array(
                'ca_id' => $row['ca_id'],
                'date' => strtotime( $row['start_date'] ),
                'raw_start_date' => $row['start_date'],
                'raw_end_date' => $end_date,
                'location' => $location,
                'duration' => BooklyLib\Utils\DateTime::secondsToInterval( $row['duration'] ),
                'full_day' => $row['duration'] >= DAY_IN_SECONDS,
                'start_date' => $start_date,
                'start_time' => $start_time,
                'start_tz' => $start_tz,
                'staff_name' => $staff_name . ( $row['staff_any'] ? $postfix_any : '' ),
                'service' => array(
                    'title' => $row['service_title'],
                    'extras' => $extras,
                ),
                'category' => $category_name,
                'status' => $row['appointment_status_text'],
                'status_code' => $row['status'],
                'price' => $price,
                'payment_id' => $row['payment_id'],
                'join_online_meeting_url' => $is_free_status ? '' : BooklyLib\Proxy\Shared::buildOnlineMeetingJoinUrl( '', $appointment, self::$customer ),
                'online_meeting_provider' => $is_free_status ? '' : $row['online_meeting_provider'],
                'online_meeting_url' => $is_free_status ? '' : BooklyLib\Proxy\Shared::buildOnlineMeetingUrl( '', $appointment, self::$customer ),
                'allow_cancel' => $allow_cancel,
                'allow_reschedule' => $allow_reschedule,
                'custom_fields' => $custom_fields,
            );
        }

        $data = array_values( $data );

        wp_send_json( array(
            'draw' => (int) self::parameter( 'draw' ),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ) );
    }

    public static function saveProfile()
    {
        $request = self::getRequest();
        $profile_data = $request->getAll();
        $columns = $request->get( 'columns', array() );
        $info_fields = array();
        foreach ( self::getRequest()->get( 'info_fields', array() ) as $id => $value ) {
            $info_fields[ $id ] = compact( 'id', 'value' );
        }
        unset( $profile_data['columns'], $profile_data['info_fields'] );
        $response = array( 'success' => true, 'errors' => array() );

        // Check errors
        $info_errors = BooklyLib\Proxy\CustomerInformation::validate( $response['errors'], $info_fields );
        if ( isset( $info_errors['info_fields'] ) ) {
            foreach ( $info_errors['info_fields'] as $field_id => $error ) {
                if ( in_array( 'customer_information_' . $field_id, $columns ) ) {
                    $response['errors']['info_fields'][ $field_id ] = $error;
                }
            }
        }
        foreach ( $profile_data as $field => $value ) {
            $errors = array();
            switch ( $field ) {
                case 'last_name':
                case 'first_name':
                case 'full_name':
                    $errors = self::_validateProfile( 'name', $profile_data );
                    break;
                case 'email':
                    $errors = self::_validateProfile( 'email', $profile_data );
                    break;
                case 'phone':
                    $errors = self::_validateProfile( 'phone', $profile_data );
                    break;
                case 'birthday':
                    $errors = self::_validateProfile( 'birthday', $profile_data );
                    break;
                case 'country':
                case 'state':
                case 'postcode':
                case 'city':
                case 'street':
                case 'street_number':
                case 'additional_address':
                    $errors = self::_validateProfile( 'address', $profile_data );
                    break;

            }
            $response['errors'] = array_merge( $response['errors'], $errors );
        }

        if ( empty( $response['errors'] ) && $profile_data['current_password'] ) {
            // Update WordPress password
            $user = get_userdata( self::$customer->getWpUserId() );
            if ( $user ) {
                if ( ! wp_check_password( html_entity_decode( $profile_data['current_password'] ), $user->data->user_pass ) ) {
                    $response['errors']['current_password'][] = __( 'Wrong current password', 'bookly' );
                }
            }
            if ( $profile_data['new_password_1'] == '' ) {
                $response['errors']['new_password_1'][] = __( 'Required', 'bookly' );
            }
            if ( $profile_data['new_password_2'] == '' ) {
                $response['errors']['new_password_2'][] = __( 'Required', 'bookly' );
            }
            if ( $profile_data['new_password_1'] != $profile_data['new_password_2'] ) {
                $response['errors']['new_password_2'][] = __( 'Passwords don\'t match', 'bookly' );
            }
            if ( empty( $response['errors'] ) ) {
                wp_set_password( html_entity_decode( $profile_data['new_password_1'] ), self::$customer->getWpUserId() );
            }
        }

        if ( empty( $response['errors'] ) ) {
            // Save profile
            foreach ( $columns as $column ) {
                switch ( $column ) {
                    case 'name':
                        if ( BooklyLib\Config::showFirstLastName() ) {
                            self::$customer
                                ->setFirstName( $profile_data['first_name'] )
                                ->setLastName( $profile_data['last_name'] );
                        } else {
                            self::$customer->setFullName( $profile_data['full_name'] );
                        }
                        break;
                    case 'email':
                        self::$customer->setEmail( $profile_data['email'] );
                        break;
                    case 'phone':
                        self::$customer->setPhone( $profile_data['phone'] );
                        break;
                    case 'birthday':
                        $day = isset( $profile_data['birthday']['day'] ) ? (int) $profile_data['birthday']['day'] : 1;
                        $month = isset( $profile_data['birthday']['month'] ) ? (int) $profile_data['birthday']['month'] : 1;
                        $year = isset( $profile_data['birthday']['year'] ) ? (int) $profile_data['birthday']['year'] : date( 'Y' );

                        self::$customer->setBirthday( sprintf( '%04d-%02d-%02d', $year, $month, $day ) );
                        break;
                    case 'address':
                        self::$customer
                            ->setCountry( isset( $profile_data['country'] ) ? $profile_data['country'] : self::$customer->getCountry() )
                            ->setState( isset( $profile_data['state'] ) ? $profile_data['state'] : self::$customer->getState() )
                            ->setPostcode( isset( $profile_data['postcode'] ) ? $profile_data['postcode'] : self::$customer->getPostcode() )
                            ->setCity( isset( $profile_data['city'] ) ? $profile_data['city'] : self::$customer->getCity() )
                            ->setStreet( isset( $profile_data['street'] ) ? $profile_data['street'] : self::$customer->getStreet() )
                            ->setStreetNumber( isset( $profile_data['street_number'] ) ? $profile_data['street_number'] : self::$customer->getStreetNumber() )
                            ->setAdditionalAddress( isset( $profile_data['additional_address'] ) ? $profile_data['additional_address'] : self::$customer->getAdditionalAddress() );
                        break;
                    case 'full_address':
                        self::$customer->setFullAddress( isset( $profile_data['full_address'] ) ? $profile_data['full_address'] : self::$customer->getFullAddress() );
                        break;
                }
            }
            if ( ! empty( $info_fields ) ) {
                $info_fields = BooklyLib\Proxy\CustomerInformation::prepareInfoFields( $info_fields ) ?: array();
                self::$customer->setInfoFields( json_encode( $info_fields ) );
                BooklyLib\Proxy\Files::attachCIFiles( $info_fields, self::$customer );
            }
            self::$customer->save();
        } else {
            $response['success'] = false;
        }

        wp_send_json( $response );
    }

    /**
     * Validate profile data
     *
     * @param string $field
     * @param array $profile_data
     * @return array
     */
    private static function _validateProfile( $field, $profile_data )
    {
        $validator = new BooklyLib\Validator();
        switch ( $field ) {
            case 'email':
                $validator->validateEmail( 'email', $profile_data );
                break;
            case 'name':
                if ( BooklyLib\Config::showFirstLastName() ) {
                    $validator->validateName( 'first_name', $profile_data['first_name'] );
                    $validator->validateName( 'last_name', $profile_data['last_name'] );
                } else {
                    $validator->validateName( 'full_name', $profile_data['full_name'] );
                }
                break;
            case 'phone':
                $validator->validatePhone( 'phone', $profile_data['phone'], BooklyLib\Config::phoneRequired() );
                break;
            case 'birthday':
                $validator->validateBirthday( $field, $profile_data[ $field ] );
                break;
            case 'address':
                $address_show_fields = (array) get_option( 'bookly_cst_address_show_fields', array() );
                foreach ( $address_show_fields as $field_name => $data ) {
                    if ( $data['show'] ) {
                        $validator->validateAddress( $field_name, $profile_data[ $field_name ], BooklyLib\Config::addressRequired() );
                    }
                }
                break;
        }

        return $validator->getErrors();
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