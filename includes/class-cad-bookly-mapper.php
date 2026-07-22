<?php
/**
 * CAD Scheduler — Bookly Mapper
 *
 * Code Snippets: create snippet "CAD — Bookly Mapper", priority 11, run site-wide.
 * Plugin:        require_once CAD_SCHEDULER_DIR . 'includes/class-cad-bookly-mapper.php';
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'CAD_Bookly_Mapper', false ) ) {
	return;
}

/**
 * Converts Bookly rows into frontend-neutral CAD payloads.
 */
class CAD_Bookly_Mapper {

	/**
	 * Map a Bookly staff row to a CAD table definition.
	 *
	 * @param array<string, mixed> $staff_row Raw staff row.
	 * @return array<string, string>
	 */
	public function map_staff_table( array $staff_row ) {
		return array(
			'id'   => (string) ( $staff_row['id'] ?? '' ),
			'name' => (string) ( $staff_row['full_name'] ?? '' ),
		);
	}

	/**
	 * Map Bookly staff rows to CAD table definitions.
	 *
	 * @param array<int, array<string, mixed>> $staff_rows Raw staff rows.
	 * @return array<int, array<string, string>>
	 */
	public function map_staff_tables( array $staff_rows ) {
		$tables = array();

		foreach ( $staff_rows as $staff_row ) {
			$table = $this->map_staff_table( $staff_row );

			if ( '' !== $table['id'] ) {
				$tables[] = $table;
			}
		}

		return $tables;
	}

	/**
	 * Map a Bookly appointment row to the CAD appointment contract.
	 *
	 * @param array<string, mixed> $row Raw appointment row.
	 * @return array<string, string>|null
	 */
	public function map_appointment( array $row ) {
		$id       = (string) ( $row['id'] ?? '' );
		$table_id = (string) ( $row['staff_id'] ?? '' );
		$start    = $this->to_iso_datetime( $row['start_date'] ?? '' );

		if ( '' === $id || '' === $table_id || null === $start ) {
			return null;
		}

		$end = $this->resolve_end_datetime( $row, $start );

		return array(
			'id'       => $id,
			'tableId'  => $table_id,
			'start'    => $start,
			'end'      => $end,
			'customer' => (string) ( $row['customer_name'] ?? '' ),
			'service'  => (string) ( $row['service_title'] ?? '' ),
			'status'   => (string) ( $row['appointment_status'] ?? '' ),
		);
	}

	/**
	 * Map Bookly appointment rows to CAD appointments.
	 *
	 * @param array<int, array<string, mixed>> $rows Raw appointment rows.
	 * @return array<int, array<string, string>>
	 */
	public function map_appointments( array $rows ) {
		$appointments = array();

		foreach ( $rows as $row ) {
			$mapped = $this->map_appointment( $row );

			if ( null !== $mapped ) {
				$appointments[] = $mapped;
			}
		}

		return $appointments;
	}

	/**
	 * Convert a Bookly datetime value to ISO-8601 in the site timezone.
	 *
	 * @param string $value Raw datetime string.
	 * @return string|null
	 */
	private function to_iso_datetime( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		try {
			$datetime = new DateTimeImmutable( $value, wp_timezone() );
		} catch ( Exception $exception ) {
			return null;
		}

		return $datetime->format( DateTimeInterface::ATOM );
	}

	/**
	 * Resolve an appointment end datetime from Bookly data.
	 *
	 * @param array<string, mixed> $row   Raw appointment row.
	 * @param string               $start ISO start datetime.
	 * @return string
	 */
	private function resolve_end_datetime( array $row, $start ) {
		$end = $this->to_iso_datetime( $row['end_date'] ?? '' );

		if ( null !== $end ) {
			return $end;
		}

		$duration = (int) ( $row['service_duration'] ?? 0 );

		try {
			$start_datetime = new DateTimeImmutable( $start );
		} catch ( Exception $exception ) {
			return $start;
		}

		if ( $duration > 0 ) {
			return $start_datetime->add( new DateInterval( 'PT' . $duration . 'S' ) )->format( DateTimeInterface::ATOM );
		}

		return $start;
	}
}
