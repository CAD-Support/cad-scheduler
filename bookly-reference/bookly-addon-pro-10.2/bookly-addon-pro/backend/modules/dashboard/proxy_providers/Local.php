<?php
namespace BooklyPro\Backend\Modules\Dashboard\ProxyProviders;

use Bookly\Backend\Modules\Dashboard\Proxy;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;

class Local extends Proxy\Pro
{
    /**
     * Analytics rows + totals (staff × service breakdown) for the combined dashboard
     * endpoint — the page loads everything in one round-trip. Accepts the RAW filter:
     * empty staff / services mean "all". Returns the datatable-ready payload.
     *
     * @inheritDoc
     */
    public static function getDashboardAnalytics( $range, $based_on, $filter )
    {
        $staff_ids = isset( $filter['staff'] ) ? $filter['staff'] : array();
        if ( $staff_ids === 'all' || empty( $staff_ids ) ) {
            $staff_ids = BooklyLib\Entities\Staff::query()->fetchCol( 'id' );
        }

        if ( isset( $filter['services'] ) && ! empty( $filter['services'] ) ) {
            $service_ids = array_map( function ( $id ) { return $id ?: null; }, $filter['services'] );
        } else {
            // All: Custom (NULL service) + every simple service.
            $service_ids = array_merge( array( null ), array_map( 'intval', BooklyLib\Entities\Service::query()->where( 'type', 'simple' )->fetchCol( 'id' ) ) );
        }

        $postfix_archived = sprintf( ' (%s)', __( 'Archived', 'bookly' ) );

        $data = array();
        foreach ( $staff_ids as $staff_id ) {
            foreach ( $service_ids as $service_id ) {
                $staff = BooklyLib\Entities\Staff::find( $staff_id );
                $data[ $staff_id ][ $service_id ] = array(
                    'staff'   => $staff->getFullName() . ( $staff->getVisibility() == 'archive' ? $postfix_archived : '' ),
                    'service' => $service_id ? BooklyLib\Entities\Service::find( $service_id )->getTitle() : __( 'Custom', 'bookly' ),
                    'appointments' => array(
                        'total' => 0,
                        'pending' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                        'cancelled' => 0,
                        'waitlisted' => 0,
                    ),
                    'customers' => array(
                        'total' => array(),
                        'new' => array(),
                    ),
                    'revenue' => array(
                        'total' => array(),
                    ),
                );
            }
        }

        list ( $start, $end ) = explode( ' - ', $range, 2 );
        $end = date( 'Y-m-d', strtotime( '+1 day', strtotime( $end ) ) );

        $query = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
            ->select( 'ca.appointment_id, ca.customer_id, ca.created_at, ca.status, a.staff_id, a.service_id, a.start_date, p.id AS payment_id, p.paid' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->leftJoin( 'Customer', 'c', 'c.id = ca.customer_id' )
            ->leftJoin( 'Service', 's', 's.id = a.service_id' )
            ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
            ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
            ->whereIn( 'a.staff_id', $staff_ids );

        switch ( $based_on ) {
            case 'start_date':
                $query->whereBetween( 'a.start_date', $start, $end );
                break;
            case 'created_at':
            default:
                $query->whereBetween( 'ca.created_at', $start, $end );
                break;
        }

        if ( array_search( null, $service_ids, true ) !== false ) {
            $where_raw = 'a.service_id IS NULL';
            $service_ids_filtered = array_filter( $service_ids );
            if ( ! empty ( $service_ids_filtered ) ) {
                $where_raw .= sprintf( ' OR a.service_id IN (%s)', implode( ',', $service_ids_filtered ) );
            }
            $query->whereRaw( $where_raw, array() );
        } else {
            $query->whereIn( 'a.service_id', $service_ids );
        }

        $custom_statuses = BooklyLib\Proxy\CustomStatuses::getAll() ?: array();
        $payments = array();

        $rows = $query->fetchArray();

        // Pre-compute each customer's first appointment date (by the same column the period
        // is based on) in ONE grouped query, mirroring the KPI card's definition of "new":
        // the customer's first-ever appointment within the staff/service filter falls in the
        // period. Replaces the previous per-row EXISTS sub-query (N+1) — strictly fewer
        // queries — and fixes the divergence where a later-booked appointment with an earlier
        // start_date was wrongly treated as a first visit.
        $first_seen   = array();
        $customer_ids = array();
        foreach ( $rows as $row ) {
            $customer_ids[ $row['customer_id'] ] = true;
        }
        if ( $customer_ids ) {
            $min_query = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                ->select( sprintf( 'ca.customer_id AS customer_id, MIN(%s) AS first_date', $based_on === 'start_date' ? 'a.start_date' : 'ca.created_at' ) )
                ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
                ->whereIn( 'ca.customer_id', array_keys( $customer_ids ) )
                ->whereIn( 'a.staff_id', $staff_ids )
                ->groupBy( 'ca.customer_id' );
            if ( array_search( null, $service_ids, true ) !== false ) {
                $min_where_raw   = 'a.service_id IS NULL';
                $min_service_ids = array_filter( $service_ids );
                if ( ! empty ( $min_service_ids ) ) {
                    $min_where_raw .= sprintf( ' OR a.service_id IN (%s)', implode( ',', $min_service_ids ) );
                }
                $min_query->whereRaw( $min_where_raw, array() );
            } else {
                $min_query->whereIn( 'a.service_id', $service_ids );
            }
            foreach ( $min_query->fetchArray() as $min_row ) {
                $first_seen[ $min_row['customer_id'] ] = $min_row['first_date'];
            }
        }
        // Period bounds for the "new" test: [start 00:00, end 00:00) — $end already holds the
        // day after the requested range, so this matches the KPI card's half-open window.
        $new_from = $start . ' 00:00:00';
        $new_to   = $end . ' 00:00:00';

        // Each new customer is credited to a single cell (first touch), so per-cell "new"
        // sums up to the Total instead of overlapping across cells.
        $new_attributed = array();

        foreach ( $rows as $row ) {
            $record = &$data[ $row['staff_id'] ][ $row['service_id'] ];
            switch ( $row['status'] ) {
                case BooklyLib\Entities\CustomerAppointment::STATUS_PENDING:
                    ++ $record['appointments']['pending'];
                    break;
                case BooklyLib\Entities\CustomerAppointment::STATUS_APPROVED:
                case BooklyLib\Entities\CustomerAppointment::STATUS_DONE: // done ≈ approved (optional/automated status)
                    ++ $record['appointments']['approved'];
                    break;
                case BooklyLib\Entities\CustomerAppointment::STATUS_REJECTED:
                    ++ $record['appointments']['rejected'];
                    break;
                case BooklyLib\Entities\CustomerAppointment::STATUS_CANCELLED:
                    ++ $record['appointments']['cancelled'];
                    break;
                case BooklyLib\Entities\CustomerAppointment::STATUS_WAITLISTED:
                    ++ $record['appointments']['waitlisted'];
                    break;
                default:
                    if ( isset ( $custom_statuses[ $row['status'] ] ) ) {
                        if ( $custom_statuses[ $row['status'] ]->getBusy() ) {
                            // Consider as APPROVED.
                            ++ $record['appointments']['approved'];
                        } else {
                            // Consider as CANCELLED.
                            ++ $record['appointments']['cancelled'];
                        }
                    }
            }
            ++ $record['appointments']['total'];
            $record['customers']['total'][ $row['customer_id'] ] = true;
            // Attribute "new" to the single cell holding the customer's first appointment
            // (first touch). Their first-ever appointment within the filter falls in this
            // period, so it is one of these rows; matching the based_on date column to the
            // pre-computed MIN places the credit in exactly one staff/service cell.
            $first_date = isset( $first_seen[ $row['customer_id'] ] ) ? $first_seen[ $row['customer_id'] ] : null;
            $row_date   = $based_on === 'start_date' ? $row['start_date'] : $row['created_at'];
            if ( $first_date !== null && $first_date >= $new_from && $first_date < $new_to
                && $row_date === $first_date && ! isset( $new_attributed[ $row['customer_id'] ] ) ) {
                $record['customers']['new'][ $row['customer_id'] ] = true;
                $new_attributed[ $row['customer_id'] ] = true;
            }
            // Revenue.
            if ( $row['payment_id'] ) {
                $record['revenue']['total'][ $row['payment_id'] ] = $row['paid'];
                $payments[ $row['payment_id'] ] = $row['paid'];
            }

            unset ( $record );
        }

        $result = array();
        // Distinct customer sets for the Total row — a customer active under several
        // staff/service cells must be counted once (keys are customer ids, '+' = set union).
        $total_customers = array();
        $new_customers   = array();
        $total  = array(
            'appointments' => array(
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'cancelled' => 0,
                'waitlisted' => 0,
            ),
            'customers' => array(
                'total' => 0,
                'new' => 0,
            ),
            'revenue' => array(
                'total' => array_sum( $payments ),
            ),
        );
        foreach ( $data as $staff_data ) {
            foreach ( $staff_data as $record ) {
                $total_customers += $record['customers']['total'];
                $new_customers   += $record['customers']['new'];

                $record['customers']['total'] = count( $record['customers']['total'] );
                $record['customers']['new'] = count( $record['customers']['new'] );

                $record['revenue']['total'] = array_sum( $record['revenue']['total'] );
                $record['revenue']['total_formatted'] = BooklyLib\Utils\Price::format( $record['revenue']['total'] );

                $result[] = $record;

                $total['appointments']['total'] += $record['appointments']['total'];
                $total['appointments']['pending'] += $record['appointments']['pending'];
                $total['appointments']['approved'] += $record['appointments']['approved'];
                $total['appointments']['rejected'] += $record['appointments']['rejected'];
                $total['appointments']['cancelled'] += $record['appointments']['cancelled'];
                $total['appointments']['waitlisted'] += $record['appointments']['waitlisted'];
            }
        }
        $total['customers']['total'] = count( $total_customers );
        $total['customers']['new']   = count( $new_customers );
        $total['revenue']['total_formatted'] = BooklyLib\Utils\Price::format( $total['revenue']['total'] );

        return array(
            'data' => $result,
            'recordsTotal' => count( $result ),
            'recordsFiltered' => count( $result ),
            'total' => $total,
        );
    }

    /**
     * @inheritDoc
     */
    public static function renderAnalytics()
    {
        self::enqueueScripts( array(
            'module' => array( 'js/dashboard-pro.js' => array( 'bookly-backend-globals' ), ),
        ) );
        $datatables = BooklyLib\Utils\Tables::getSettings( 'analytics' );

        // Flatten the categorised staff / service drop-down data into flat option
        // lists for the in-datatable checkboxGroup filters.
        $dropdown_data = array(
            'service' => BooklyLib\Utils\Common::getServiceDataForDropDown( 's.type = "simple"' ),
            'staff'   => Lib\ProxyProviders\Local::getStaffDataForDropDown()
        );
        $staff_options = array();
        foreach ( $dropdown_data['staff'] as $category ) {
            foreach ( $category['items'] as $st ) {
                $staff_options[] = array( 'value' => (int) $st['id'], 'label' => $st['full_name'] );
            }
        }
        $service_options = array( array( 'value' => 0, 'label' => __( 'Custom', 'bookly' ) ) );
        foreach ( $dropdown_data['service'] as $category ) {
            foreach ( $category['items'] as $sv ) {
                $service_options[] = array( 'value' => (int) $sv['id'], 'label' => $sv['title'] );
            }
        }

        wp_localize_script( 'bookly-dashboard-pro.js', 'BooklyAnalyticsL10n', array(
            'datatables' => $datatables,
            'zeroRecords' => __( 'No appointments for selected period.', 'bookly' ),
            'emptyTable' => __( 'No appointments for selected period.', 'bookly' ),
            'search' => __( 'Search', 'bookly' ) . '…',
            'total' => __( 'Total', 'bookly' ),
            'staffLabel' => BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_employee' ),
            'serviceLabel' => BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' ),
            'staffOptions' => $staff_options,
            'serviceOptions' => $service_options,
            'exportCsv' => __( 'Export to CSV', 'bookly' ) . '…',
            'print' => __( 'Print', 'bookly' ) . '…',
            'filter' => $datatables['analytics']['settings']['filter'],
        ) );

        self::renderTemplate( 'analytics' );
    }
}
