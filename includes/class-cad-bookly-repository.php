<?php
/**
 * CAD Scheduler — Bookly Repository
 *
 * Code Snippets: create snippet "CAD — Bookly Repository", priority 10, run site-wide.
 * Plugin:        require_once CAD_SCHEDULER_DIR . 'includes/class-cad-bookly-repository.php';
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'CAD_Bookly_Repository', false ) ) {
	return;
}

/**
 * Queries Bookly staff and appointment records.
 */
class CAD_Bookly_Repository {

	/**
	 * Determine whether Bookly database tables are available.
	 *
	 * @return bool
	 */
	public static function is_available() {
		global $wpdb;

		$table = $wpdb->prefix . 'bookly_staff';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * Retrieve active Bookly staff as CAD table definitions.
	 *
	 * Each Bookly staff member represents one physical table.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_staff_tables() {
		global $wpdb;

		if ( ! self::is_available() ) {
			return array();
		}

		$table = $wpdb->prefix . 'bookly_staff';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT id, full_name
			FROM {$table}
			WHERE visibility != 'private'
			ORDER BY position ASC, full_name ASC",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return $rows;
	}

	/**
	 * Retrieve raw Bookly appointment rows for a calendar date.
	 *
	 * @param string $date Date in YYYY-MM-DD format.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_appointments_for_date( $date ) {
		global $wpdb;

		if ( ! self::is_available() ) {
			return array();
		}

		$appointments_table = $wpdb->prefix . 'bookly_appointments';
		$customer_appts     = $wpdb->prefix . 'bookly_customer_appointments';
		$customers_table    = $wpdb->prefix . 'bookly_customers';
		$services_table     = $wpdb->prefix . 'bookly_services';

		$sql = "SELECT
				a.id,
				a.staff_id,
				a.start_date,
				a.end_date,
				a.service_id,
				ca.status AS appointment_status,
				COALESCE(
					NULLIF( c.full_name, '' ),
					TRIM( CONCAT( COALESCE( c.first_name, '' ), ' ', COALESCE( c.last_name, '' ) ) )
				) AS customer_name,
				s.title AS service_title,
				s.duration AS service_duration
			FROM {$appointments_table} a
			INNER JOIN {$customer_appts} ca ON ca.appointment_id = a.id
			LEFT JOIN {$customers_table} c ON c.id = ca.customer_id
			LEFT JOIN {$services_table} s ON s.id = a.service_id
			WHERE DATE( a.start_date ) = %s
			ORDER BY a.start_date ASC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $date ), ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return $this->dedupe_appointments( $rows );
	}

	/**
	 * Keep the first customer appointment row when Bookly returns multiples.
	 *
	 * @param array<int, array<string, mixed>> $rows Raw rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function dedupe_appointments( array $rows ) {
		$unique = array();

		foreach ( $rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );

			if ( '' === $id || isset( $unique[ $id ] ) ) {
				continue;
			}

			$unique[ $id ] = $row;
		}

		return array_values( $unique );
	}
}
