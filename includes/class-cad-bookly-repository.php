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

	/** Bumped when staff query logic changes — used by pipeline diagnostics. */
	const BUILD = '2.5.1-staff-pipeline';

	public static function is_available() {
		global $wpdb;
		$table = $wpdb->prefix . 'bookly_staff';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * Visibility predicate used by get_staff_tables().
	 *
	 * @return string
	 */
	public function staff_visibility_sql() {
		$visibility_sql = apply_filters(
			'cad_scheduler_staff_visibility_sql',
			"(COALESCE(visibility, '') NOT IN ('archive'))"
		);
		if ( ! is_string( $visibility_sql ) || '' === trim( $visibility_sql ) ) {
			$visibility_sql = "(COALESCE(visibility, '') NOT IN ('archive'))";
		}
		return $visibility_sql;
	}

	/**
	 * @return string
	 */
	public function staff_order_sql() {
		$order_sql = apply_filters(
			'cad_scheduler_staff_order_sql',
			'position ASC, full_name ASC'
		);
		if ( ! is_string( $order_sql ) || '' === trim( $order_sql ) ) {
			$order_sql = 'position ASC, full_name ASC';
		}
		return $order_sql;
	}

	/**
	 * Visible Bookly staff → CAD calendar columns.
	 *
	 * Matches Bookly admin calendar: every non-archived staff member, ordered by
	 * Bookly `position`. No name allowlists — future resources appear automatically.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_staff_tables() {
		global $wpdb;

		if ( ! self::is_available() ) {
			return array();
		}

		$table           = $wpdb->prefix . 'bookly_staff';
		$visibility_sql  = $this->staff_visibility_sql();
		$order_sql       = $this->staff_order_sql();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			"SELECT id, full_name, position, visibility
			FROM {$table}
			WHERE {$visibility_sql}
			ORDER BY {$order_sql}",
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Full staff inventory for pipeline tracing (includes archived).
	 * Does not hardcode names — returns whatever Bookly stores.
	 *
	 * @return array{
	 *   build: string,
	 *   table: string,
	 *   columns: string[],
	 *   visibility_sql: string,
	 *   order_sql: string,
	 *   visibility_values: array<string,int>,
	 *   all: array<int, array<string,mixed>>,
	 *   visible: array<int, array<string,mixed>>,
	 *   excluded: array<int, array<string,mixed>>,
	 *   counts: array<string,int>
	 * }
	 */
	public function debug_staff_pipeline() {
		global $wpdb;

		$empty = array(
			'build'             => self::BUILD,
			'table'             => '',
			'columns'           => array(),
			'visibility_sql'    => $this->staff_visibility_sql(),
			'order_sql'         => $this->staff_order_sql(),
			'visibility_values' => array(),
			'all'               => array(),
			'visible'           => array(),
			'excluded'          => array(),
			'counts'            => array(
				'all'      => 0,
				'visible'  => 0,
				'excluded' => 0,
			),
			'error'             => null,
		);

		if ( ! self::is_available() ) {
			$empty['error'] = 'bookly_staff table not found';
			return $empty;
		}

		$table = $wpdb->prefix . 'bookly_staff';
		$empty['table'] = $table;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$col_rows = $wpdb->get_results( "SHOW COLUMNS FROM {$table}", ARRAY_A );
		$columns  = array();
		if ( is_array( $col_rows ) ) {
			foreach ( $col_rows as $col ) {
				if ( ! empty( $col['Field'] ) ) {
					$columns[] = (string) $col['Field'];
				}
			}
		}
		$empty['columns'] = $columns;

		$has_visibility = in_array( 'visibility', $columns, true );
		$has_position   = in_array( 'position', $columns, true );

		$select = array( 'id', 'full_name' );
		if ( $has_position ) {
			$select[] = 'position';
		}
		if ( $has_visibility ) {
			$select[] = 'visibility';
		}
		// Common Bookly extras if present (not assumed).
		foreach ( array( 'category_id', 'wp_user_id' ) as $extra ) {
			if ( in_array( $extra, $columns, true ) ) {
				$select[] = $extra;
			}
		}

		$order = $has_position ? 'position ASC, full_name ASC' : 'id ASC';
		$sql   = 'SELECT ' . implode( ', ', $select ) . " FROM {$table} ORDER BY {$order}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$all = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $all ) ) {
			$empty['error'] = 'staff query failed: ' . (string) $wpdb->last_error;
			return $empty;
		}

		$visibility_values = array();
		$normalized_all    = array();
		foreach ( $all as $row ) {
			$vis = array_key_exists( 'visibility', $row ) ? (string) $row['visibility'] : '';
			if ( ! isset( $visibility_values[ $vis ] ) ) {
				$visibility_values[ $vis ] = 0;
			}
			$visibility_values[ $vis ]++;

			$archived = ( 'archive' === strtolower( trim( $vis ) ) );
			$normalized_all[] = array(
				'id'         => (string) ( $row['id'] ?? '' ),
				'name'       => (string) ( $row['full_name'] ?? '' ),
				'visibility' => $vis,
				'archived'   => $archived,
				'position'   => isset( $row['position'] ) ? (int) $row['position'] : null,
				'category_id'=> isset( $row['category_id'] ) ? (string) $row['category_id'] : null,
				'wp_user_id' => isset( $row['wp_user_id'] ) ? (string) $row['wp_user_id'] : null,
			);
		}

		$visible_rows = $this->get_staff_tables();
		$visible_ids  = array();
		$visible_norm = array();
		foreach ( $visible_rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );
			$visible_ids[ $id ] = true;
			$vis = (string) ( $row['visibility'] ?? '' );
			$visible_norm[] = array(
				'id'         => $id,
				'name'       => (string) ( $row['full_name'] ?? '' ),
				'visibility' => $vis,
				'archived'   => ( 'archive' === strtolower( trim( $vis ) ) ),
				'position'   => isset( $row['position'] ) ? (int) $row['position'] : null,
			);
		}

		$excluded = array();
		foreach ( $normalized_all as $row ) {
			if ( ! isset( $visible_ids[ $row['id'] ] ) ) {
				$excluded[] = $row;
			}
		}

		return array(
			'build'             => self::BUILD,
			'table'             => $table,
			'columns'           => $columns,
			'visibility_sql'    => $this->staff_visibility_sql(),
			'order_sql'         => $this->staff_order_sql(),
			'visibility_values' => $visibility_values,
			'all'               => $normalized_all,
			'visible'           => $visible_norm,
			'excluded'          => $excluded,
			'counts'            => array(
				'all'      => count( $normalized_all ),
				'visible'  => count( $visible_norm ),
				'excluded' => count( $excluded ),
			),
			'error'             => null,
		);
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
