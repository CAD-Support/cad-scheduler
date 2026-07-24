<?php
/**
 * CAD Scheduler — Bookly Repository
 *
 * Code Snippets: priority 10 — paste entire file.
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'CAD_Bookly_Repository', false ) ) {
	return;
}

class CAD_Bookly_Repository {

	public static function is_available() {
		global $wpdb;
		$table = $wpdb->prefix . 'bookly_staff';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	public function get_staff_tables() {
		global $wpdb;

		if ( ! self::is_available() ) {
			return array();
		}

		$table = $wpdb->prefix . 'bookly_staff';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT id, full_name FROM {$table}
			WHERE visibility != 'archive'
			ORDER BY position ASC, full_name ASC",
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	public function get_appointments_for_date( $date ) {
		global $wpdb;

		if ( ! self::is_available() ) {
			return array();
		}

		$sql = "SELECT
				a.id, a.staff_id, a.start_date,
				DATE_ADD(a.end_date, INTERVAL COALESCE(a.extras_duration, 0) SECOND) AS end_date,
				a.internal_note,
				ca.status AS appointment_status,
				ca.number_of_persons,
				p.status AS payment_status,
				COALESCE(NULLIF(c.full_name, ''), TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')))) AS customer_name,
				s.title AS service_title, s.duration AS service_duration
			FROM {$wpdb->prefix}bookly_appointments a
			INNER JOIN {$wpdb->prefix}bookly_customer_appointments ca ON ca.appointment_id = a.id
			LEFT JOIN {$wpdb->prefix}bookly_customers c ON c.id = ca.customer_id
			LEFT JOIN {$wpdb->prefix}bookly_services s ON s.id = a.service_id
			LEFT JOIN {$wpdb->prefix}bookly_payments p ON p.id = ca.payment_id
			WHERE DATE(a.start_date) = %s
			ORDER BY a.start_date ASC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $date ), ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$unique = array();
		foreach ( $rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );
			if ( $id && ! isset( $unique[ $id ] ) ) {
				$unique[ $id ] = $row;
			}
		}

		return array_values( $unique );
	}
}
