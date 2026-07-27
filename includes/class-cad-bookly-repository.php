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

	/**
	 * Custom-fields JSON expression (Bookly column name varies by install).
	 * Default: ca.custom_fields. Override via cad_scheduler_custom_fields_select_sql
	 * (e.g. COALESCE(ca.custom_fields, ca.json_data) when both columns exist).
	 *
	 * @return string Safe SQL fragment (no user input).
	 */
	private function custom_fields_select_sql() {
		$expr = apply_filters( 'cad_scheduler_custom_fields_select_sql', 'ca.custom_fields' );
		return is_string( $expr ) && '' !== trim( $expr ) ? $expr : 'ca.custom_fields';
	}

	public function get_appointments_for_date( $date ) {
		global $wpdb;

		if ( ! self::is_available() ) {
			return array();
		}

		$custom_fields_sql = $this->custom_fields_select_sql();

		$sql = "SELECT
				a.id, a.staff_id, a.service_id, a.start_date,
				DATE_ADD(a.end_date, INTERVAL COALESCE(a.extras_duration, 0) SECOND) AS end_date,
				a.internal_note,
				ca.status AS appointment_status,
				ca.number_of_persons,
				{$custom_fields_sql} AS custom_fields_json,
				COALESCE(NULLIF(c.full_name, ''), TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')))) AS customer_name,
				c.phone AS customer_phone,
				s.title AS service_title, s.duration AS service_duration
			FROM {$wpdb->prefix}bookly_appointments a
			INNER JOIN {$wpdb->prefix}bookly_customer_appointments ca ON ca.appointment_id = a.id
			LEFT JOIN {$wpdb->prefix}bookly_customers c ON c.id = ca.customer_id
			LEFT JOIN {$wpdb->prefix}bookly_services s ON s.id = a.service_id
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

	/**
	 * Update Bookly custom status on all customer-appointment rows for an appointment.
	 *
	 * @param string $appointment_id Bookly appointment id.
	 * @param string $status         Status slug (e.g. arrived, paid, deposit-paid).
	 * @return int|false Rows updated, or false on failure.
	 */
	public function update_appointment_status( $appointment_id, $status ) {
		global $wpdb;

		if ( ! self::is_available() ) {
			return false;
		}

		$appointment_id = (string) $appointment_id;
		$status         = (string) $status;
		if ( '' === $appointment_id || '' === $status ) {
			return false;
		}

		$table = $wpdb->prefix . 'bookly_customer_appointments';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'appointment_id' => $appointment_id ),
			array( '%s' ),
			array( '%s' )
		);
	}
}
