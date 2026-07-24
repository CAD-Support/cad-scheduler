<?php
namespace BooklyCoupons\Backend\Modules\Coupons;

use BooklyCoupons\Lib;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\CodeGenerator;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Get coupons list
     */
    public static function getCoupons()
    {
        global $wpdb;

        $columns = BooklyLib\Utils\Tables::filterColumns( self::parameter( 'columns' ), BooklyLib\Utils\Tables::COUPONS );
        $order = self::parameter( 'order', array() );
        $filter = self::parameter( 'filter' );

        $query = Lib\Entities\Coupon::query( 'c' );

        // Filters.
        if ( ! empty( $filter['code'] ) ) {
            $query->whereLike( 'c.code', '%' . BooklyLib\Query::escape( $filter['code'] ) . '%' );
        }
        if ( $filter['service'] != '' ) {
            $query->leftJoin( 'CouponService', 'cs', 'cs.coupon_id = c.id' )
                ->where( 'cs.service_id', $filter['service'] );
        }
        if ( $filter['staff'] != '' ) {
            $query->leftJoin( 'CouponStaff', 'cst', 'cst.coupon_id = c.id' )
                ->where( 'cst.staff_id', $filter['staff'] );
        }
        if ( $filter['customer'] != '' ) {
            $query->leftJoin( 'CouponCustomer', 'cc', 'cc.coupon_id = c.id' )
                ->where( 'cc.customer_id', $filter['customer'] );
        }
        if ( $filter['active'] ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            $query->whereRaw(
                'c.used < c.usage_limit AND (c.date_limit_start IS NULL OR %s >= c.date_limit_start) AND (c.date_limit_end IS NULL OR %s <= c.date_limit_end)',
                array( $today, $today )
            );
        }

        $ids = $query->groupBy( 'c.id' )->fetchCol( 'c.id' );

        $queries = array(
            'services' => Lib\Entities\CouponService::query()->select( 'COUNT(*) AS services, service_id, coupon_id' ),
            'staff' => Lib\Entities\CouponStaff::query()->select( 'COUNT(*) AS staff, staff_id, coupon_id' ),
            'customers' => Lib\Entities\CouponCustomer::query()->select( 'COUNT(*) AS customers, customer_id, coupon_id' )
        );
        foreach ( $queries as $query ) {
            $query->whereIn( 'coupon_id', $ids )->groupBy( 'coupon_id' );
        }

        $query = Lib\Entities\Coupon::query( 'c' )
            ->select( 'SQL_CALC_FOUND_ROWS c.*, cs.*, cst.*, cc.*, customer.full_name' )
            ->joinSelect( $queries['services'], 'cs', 'cs.coupon_id = c.id', 'LEFT' )
            ->joinSelect( $queries['staff'], 'cst', 'cst.coupon_id = c.id', 'LEFT' )
            ->joinSelect( $queries['customers'], 'cc', 'cc.coupon_id = c.id', 'LEFT' )
            ->joinSelect( BooklyLib\Entities\Customer::query(), 'customer', 'customer.id = cc.customer_id', 'LEFT' )
            ->whereIn( 'c.id', $ids )
        ;

        foreach ( $order as $sort_by ) {
            $query
                ->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? BooklyLib\Query::ORDER_DESCENDING : BooklyLib\Query::ORDER_ASCENDING );
        }

        $coupons = $query
            ->limit( self::parameter( 'length' ) )
            ->offset( self::parameter( 'start' ) )
            ->fetchArray();

        $filtered = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        foreach ( $coupons as &$coupon ) {
            $coupon['date_limit_start_formatted'] = is_null( $coupon['date_limit_start'] ) ? '' : BooklyLib\Utils\DateTime::formatDate( $coupon['date_limit_start'] );
            $coupon['date_limit_end_formatted']   = is_null( $coupon['date_limit_end'] ) ? '' : BooklyLib\Utils\DateTime::formatDate( $coupon['date_limit_end'] );
        }

        BooklyLib\Utils\Tables::updateSettings( BooklyLib\Utils\Tables::COUPONS, $columns, $order, $filter );

        wp_send_json( array(
            'draw' => (int) self::parameter( 'draw' ),
            'recordsTotal' => Lib\Entities\Coupon::query()->count(),
            'recordsFiltered' => $filtered,
            'data' => $coupons,
        ) );
    }

    /**
     * Create/update coupon
     */
    public static function saveCoupon()
    {
        $params = self::parameters();

        if ( $params['code'] === null ) {
            $params['code'] = '';
        }
        $updating_coupon = new Lib\Entities\Coupon();
        $duplicates_query = Lib\Entities\Coupon::query()->where( 'code', $params['code'] );
        if ( isset ( $params['id'] ) ) {
            $updating_coupon->load( $params['id'] );
            $duplicates_query->whereNot( 'id', $params['id'] );
        }
        $updating_coupon->setFields( $params );

        if ( $params['discount'] < 0 || $params['discount'] > 100 ) {
            wp_send_json_error( array( 'message' => __( 'Discount should be between 0 and 100.', 'bookly' ) ) );
        } elseif ( $params['deduction'] < 0 ) {
            wp_send_json_error( array( 'message' => __( 'Deduction should be a positive number.', 'bookly' ) ) );
        } elseif ( $params['min_appointments'] < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Min appointments should be greater than zero.', 'bookly' ) ) );
        } elseif ( $params['max_appointments'] !== null && $params['max_appointments'] < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Max appointments should be greater than zero.', 'bookly' ) ) );
        } elseif ( ! isset ( $params['create_series'] ) && $duplicates_query->count() > 0 ) {
            wp_send_json_error( array( 'message' => __( 'The code already exists', 'bookly' ) ) );
        } else {
            if ( isset ( $params['create_series'] ) ) {
                if ( $params['mask'] == '' ) {
                    wp_send_json_error( array( 'message' => __( 'Please enter a non empty mask.', 'bookly' ) ) );
                }
                try {
                    $codes = CodeGenerator::generateUniqueCodeSeries( '\BooklyCoupons\Lib\Entities\Coupon', $params['mask'], $params['amount'] );
                } catch ( \Exception $e ) {
                    wp_send_json_error( array( 'message' => sprintf(
                        __( 'It is not possible to generate %d codes for this mask. Only %d codes available.', 'bookly' ),
                        $params['amount'],
                        $e->getMessage()
                    ) ) );
                }
            } else {
                $codes = array( $params['code'] );
            }

            $service_ids = self::getRequest()->get( 'service_ids', array() );
            $staff_ids = self::getRequest()->get( 'staff_ids', array() );
            $customer_ids = self::getRequest()->get( 'customer_ids', array() );
            foreach ( $codes as $code ) {
                $coupon = clone $updating_coupon;
                $coupon->setCode( $code )->save();

                // Services.
                if ( empty ( $service_ids ) ) {
                    Lib\Entities\CouponService::query()
                        ->delete()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->execute();
                } else {
                    Lib\Entities\CouponService::query()
                        ->delete()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->whereNotIn( 'service_id', $service_ids )
                        ->execute();
                    $new_services = Lib\Entities\CouponService::query()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->fetchColDiff( 'service_id', $service_ids );
                    foreach ( $new_services as $service_id ) {
                        $coupon_service = new Lib\Entities\CouponService();
                        $coupon_service
                            ->setCouponId( $coupon->getId() )
                            ->setServiceId( $service_id )
                            ->save();
                    }
                }
                // Staff.
                if ( empty ( $staff_ids ) ) {
                    Lib\Entities\CouponStaff::query()
                        ->delete()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->execute();
                } else {
                    Lib\Entities\CouponStaff::query()
                        ->delete()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->whereNotIn( 'staff_id', $staff_ids )
                        ->execute();
                    $new_staff = Lib\Entities\CouponStaff::query()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->fetchColDiff( 'staff_id', $staff_ids );
                    foreach ( $new_staff as $staff_id ) {
                        $coupon_staff = new Lib\Entities\CouponStaff();
                        $coupon_staff
                            ->setCouponId( $coupon->getId() )
                            ->setStaffId( $staff_id )
                            ->save();
                    }
                }
                // Customers.
                if ( empty ( $customer_ids ) ) {
                    Lib\Entities\CouponCustomer::query()
                        ->delete()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->execute();
                } else {
                    Lib\Entities\CouponCustomer::query()
                        ->delete()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->whereNotIn( 'customer_id', $customer_ids )
                        ->execute();
                    $new_customers = Lib\Entities\CouponCustomer::query()
                        ->where( 'coupon_id', $coupon->getId() )
                        ->fetchColDiff( 'customer_id', $customer_ids );
                    foreach ( $new_customers as $customer_id ) {
                        $coupon_customer = new Lib\Entities\CouponCustomer();
                        $coupon_customer
                            ->setCouponId( $coupon->getId() )
                            ->setCustomerId( $customer_id )
                            ->save();
                    }
                }
            }

            wp_send_json_success();
        }
    }

    public static function getCouponLists()
    {
        $coupon_id = self::parameter( 'coupon_id' );
        $remote = self::parameter( 'remote' );

        $query = Lib\Entities\CouponCustomer::query( 'cc' )
            ->select( 'cc.customer_id' )
            ->where( 'coupon_id', $coupon_id );

        if ( $remote ) {
            $query->addSelect( 'customer.full_name, customer.email, customer.phone' )
                ->leftJoin( 'Customer', 'customer', 'customer.id = cc.customer_id', '\Bookly\Lib\Entities' );
        }
        $customer_id = $customers = array();
        foreach ( $query->fetchArray() as $record ) {
            if ( $record['customer_id'] ) {
                $customer_id[] = $record['customer_id'];
            }
            if ( $remote && ! isset ( $customers[ $record['customer_id'] ] ) ) {
                $name = $record['full_name'];
                if ( $record['email'] != '' || $record['phone'] != '' ) {
                    $name .= ' (' . trim( $record['email'] . ', ' . $record['phone'], ', ' ) . ')';
                }
                $customers[ $record['customer_id'] ] = array(
                    'id' => $record['customer_id'],
                    'text' => $name,
                );
            }
        }

        wp_send_json_success( array(
            'service_id' => Lib\Entities\CouponService::query()->where( 'coupon_id', $coupon_id )->fetchCol( 'service_id' ),
            'staff_id' => Lib\Entities\CouponStaff::query()->where( 'coupon_id', $coupon_id )->fetchCol( 'staff_id' ),
            'customer_id' => $customer_id,
            'customers' => array_values( $customers ),
        ) );
    }

    /**
     * Generate code.
     */
    public static function generateCode()
    {
        $mask = self::parameter( 'mask' );

        if ( $mask == '' ) {
            $mask = get_option( 'bookly_coupons_default_code_mask' );
        }
        if ( $mask == '' ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a non empty mask.', 'bookly' ) ) );
        }

        try {
            $code = CodeGenerator::generateUniqueCode( '\BooklyCoupons\Lib\Entities\Coupon', $mask );
            wp_send_json_success( compact( 'code' ) );
        } catch ( \Exception $e ) {
            wp_send_json_error( array( 'message' => __( 'All possible codes have already been generated for this mask.', 'bookly' ) ) );
        }
    }

    /**
     * Delete coupons.
     */
    public static function deleteCoupons()
    {
        $coupon_ids = array_map( 'intval', self::parameter( 'data', array() ) );
        Lib\Entities\Coupon::query()->delete()->whereIn( 'id', $coupon_ids )->execute();

        wp_send_json_success();
    }

    /**
     * Export coupons
     */
    public static function export()
    {
        $delimiter = self::parameter( 'export_customers_delimiter', ',' );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=Coupons.csv' );

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::COUPONS );

        $header = array();
        $column = array();
        foreach ( self::parameter( 'exp', array() ) as $key => $value ) {
            $header[] = $datatables['coupons']['titles'][ $key ];
            $column[] = $key;
        }

        $output = fopen( 'php://output', 'w' );
        fwrite( $output, pack( 'CCC', 0xef, 0xbb, 0xbf ) );
        fputcsv( $output, $header, $delimiter, '"', '\\' );

        $query = Lib\Entities\Coupon::query( 'c' )
            ->select( 'c.id, c.code, c.discount, c.deduction, c.usage_limit, c.used, c.date_limit_start, c.date_limit_end, c.min_appointments, c.max_appointments' )
            ->addSelect( 'GROUP_CONCAT(DISTINCT s.title) AS services,
                GROUP_CONCAT(DISTINCT st.full_name) AS staff,
                GROUP_CONCAT(DISTINCT customer.full_name) AS customers' )
            ->leftJoin( 'CouponService', 'cs', 'cs.coupon_id = c.id' )
            ->leftJoin( 'Service', 's', 's.id = cs.service_id', '\Bookly\Lib\Entities' )
            ->leftJoin( 'CouponStaff', 'cst', 'cst.coupon_id = c.id' )
            ->leftJoin( 'Staff', 'st', 'st.id = cst.staff_id', '\Bookly\Lib\Entities' )
            ->leftJoin( 'CouponCustomer', 'cc', 'cc.coupon_id = c.id' )
            ->leftJoin( 'Customer', 'customer', 'customer.id = cc.customer_id', '\Bookly\Lib\Entities' )
            ->groupBy( 'c.id' )
        ;

        if ( self::parameter( 'active' ) ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            $query->whereRaw(
                'c.used < c.usage_limit AND (c.date_limit_start IS NULL OR %s >= c.date_limit_start) AND (c.date_limit_end IS NULL OR %s <= c.date_limit_end)',
                array( $today, $today )
            );
        }

        foreach ( $query->fetchArray() as $row ) {
            $row_data = array_fill( 0, count( $column ), '' );
            foreach ( $row as $key => $value ) {
                $pos = array_search( $key, $column );
                if ( $pos !== false ) {
                    if ( ( $key == 'customers' ) && $value === null ) {
                        $value = __( 'All', 'bookly' );
                    }
                    $row_data[ $pos ] = $value;
                }
            }

            fputcsv( $output, $row_data, $delimiter, '"', '\\' );
        }

        fclose( $output );

        exit;
    }
}