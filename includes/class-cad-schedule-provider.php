<?php
/**
 * CAD Scheduler — Schedule Provider
 *
 * Code Snippets: priority 12 — paste entire file.
 *
 * Exposes the normalized appointment model to AJAX / the frontend.
 * This output is the public API contract — keep it stable and additive.
 * Repository and Mapper may change internally; do not leak Bookly schema here.
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'CAD_Schedule_Provider', false ) ) {
	return;
}

class CAD_Schedule_Provider {

	private $repository;
	private $mapper;

	public function __construct() {
		$this->repository = new CAD_Bookly_Repository();
		$this->mapper     = new CAD_Bookly_Mapper();
	}

	public function get_tables() {
		return apply_filters(
			'cad_scheduler_tables',
			$this->mapper->map_staff_tables( $this->repository->get_staff_tables() )
		);
	}

	/**
	 * Trace staff → tables with a plain-text summary that names the failing layer.
	 *
	 * @return array<string, mixed>
	 */
	public function debug_tables_pipeline() {
		$repo      = $this->repository->debug_staff_pipeline();
		$repo_rows = $this->repository->get_staff_tables();
		$mapped    = $this->mapper->map_staff_tables( $repo_rows );
		$provider  = apply_filters( 'cad_scheduler_tables', $mapped );
		if ( ! is_array( $provider ) ) {
			$provider = array();
		}

		$by_id = array();
		$all_rows = ( isset( $repo['all'] ) && is_array( $repo['all'] ) ) ? $repo['all'] : array();
		foreach ( $all_rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$by_id[ $id ] = $this->normalize_resource_row( $row );
		}

		$bookly_resources = array_values( $by_id );

		$repo_visible = array();
		foreach ( $repo_rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$base = isset( $by_id[ $id ] ) ? $by_id[ $id ] : $this->normalize_resource_row(
				array(
					'id'         => $id,
					'name'       => (string) ( $row['full_name'] ?? '' ),
					'visibility' => (string) ( $row['visibility'] ?? '' ),
					'position'   => $row['position'] ?? null,
				)
			);
			$repo_visible[] = $base;
		}

		$excluded = array();
		$excluded_rows = ( isset( $repo['excluded'] ) && is_array( $repo['excluded'] ) ) ? $repo['excluded'] : array();
		foreach ( $excluded_rows as $row ) {
			$norm                     = $this->normalize_resource_row( $row );
			$norm['excludedReason']   = $this->exclusion_reason( $norm );
			$excluded[]               = $norm;
		}

		$mapper_out = array();
		$mapped_ids = array();
		foreach ( $mapped as $t ) {
			$id = (string) ( $t['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$mapped_ids[ $id ] = true;
			$base = isset( $by_id[ $id ] ) ? $by_id[ $id ] : $this->normalize_resource_row(
				array(
					'id'   => $id,
					'name' => (string) ( $t['name'] ?? '' ),
				)
			);
			$mapper_out[] = $base;
		}

		$dropped_mapper = array();
		foreach ( $repo_visible as $row ) {
			if ( ! isset( $mapped_ids[ $row['id'] ] ) ) {
				$row['excludedReason'] = 'Dropped by mapper (missing id or empty id).';
				$dropped_mapper[]      = $row;
			}
		}

		$provider_out = array();
		$provider_ids = array();
		foreach ( $provider as $t ) {
			$id = (string) ( $t['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$provider_ids[ $id ] = true;
			$base = isset( $by_id[ $id ] ) ? $by_id[ $id ] : $this->normalize_resource_row(
				array(
					'id'   => $id,
					'name' => (string) ( $t['name'] ?? '' ),
				)
			);
			$provider_out[] = $base;
		}

		$dropped_provider = array();
		foreach ( $mapper_out as $row ) {
			if ( ! isset( $provider_ids[ $row['id'] ] ) ) {
				$row['excludedReason'] = 'Dropped by cad_scheduler_tables filter.';
				$dropped_provider[]    = $row;
			}
		}

		$has_tables_filter = false;
		global $wp_filter;
		if ( isset( $wp_filter['cad_scheduler_tables'] ) ) {
			$has_tables_filter = true;
		}

		$counts = array(
			'bookly'     => count( $bookly_resources ),
			'repository' => count( $repo_visible ),
			'mapper'     => count( $mapper_out ),
			'provider'   => count( $provider_out ),
			'ajax'       => count( $provider_out ), // same payload as data.tables
			'ui'         => null, // filled by browser
		);

		$failing_layer = $this->detect_failing_layer( $counts, $excluded, $dropped_mapper, $dropped_provider );

		$report = array(
			'repository_build' => CAD_Bookly_Repository::BUILD,
			'visibility_sql'   => isset( $repo['visibility_sql'] ) ? $repo['visibility_sql'] : '',
			'order_sql'        => isset( $repo['order_sql'] ) ? $repo['order_sql'] : '',
			'tables_filter_registered' => $has_tables_filter,
			'counts'           => $counts,
			'failingLayer'     => $failing_layer,
			'stages'           => array(
				'bookly'     => $bookly_resources,
				'repository' => $repo_visible,
				'mapper'     => $mapper_out,
				'provider'   => $provider_out,
				'ajax'       => $provider_out,
			),
			'excluded'         => array(
				'repository' => $excluded,
				'mapper'     => $dropped_mapper,
				'provider'   => $dropped_provider,
			),
			'visibility_values'=> isset( $repo['visibility_values'] ) ? $repo['visibility_values'] : array(),
			'columns'          => isset( $repo['columns'] ) ? $repo['columns'] : array(),
			'error'            => isset( $repo['error'] ) ? $repo['error'] : null,
		);

		$report['summary'] = $this->format_pipeline_summary( $report );

		return $report;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array{id: string, name: string, position: int|null, visibility: string, archived: bool}
	 */
	private function normalize_resource_row( array $row ) {
		$id   = (string) ( $row['id'] ?? '' );
		$name = (string) ( $row['name'] ?? $row['full_name'] ?? '' );
		$vis  = (string) ( $row['visibility'] ?? '' );
		$archived = array_key_exists( 'archived', $row )
			? (bool) $row['archived']
			: ( 'archive' === strtolower( trim( $vis ) ) );

		$position = null;
		if ( array_key_exists( 'position', $row ) && null !== $row['position'] && '' !== $row['position'] ) {
			$position = (int) $row['position'];
		}

		return array(
			'id'         => $id,
			'name'       => $name,
			'position'   => $position,
			'visibility' => $vis,
			'archived'   => $archived,
		);
	}

	/**
	 * @param array{id: string, name: string, position: int|null, visibility: string, archived: bool} $row
	 */
	private function exclusion_reason( array $row ) {
		if ( ! empty( $row['archived'] ) || 'archive' === strtolower( trim( (string) $row['visibility'] ) ) ) {
			return 'archived = true (visibility=archive)';
		}
		$vis = trim( (string) $row['visibility'] );
		if ( '' === $vis ) {
			return 'excluded by visibility SQL (empty visibility)';
		}
		return 'excluded by visibility SQL (visibility=' . $vis . ')';
	}

	/**
	 * @param array<string, int|null> $counts
	 * @param array                   $excluded
	 * @param array                   $dropped_mapper
	 * @param array                   $dropped_provider
	 */
	private function detect_failing_layer( array $counts, array $excluded, array $dropped_mapper, array $dropped_provider ) {
		if ( ! empty( $dropped_provider ) ) {
			return 'provider';
		}
		if ( ! empty( $dropped_mapper ) ) {
			return 'mapper';
		}
		if ( (int) $counts['repository'] < (int) $counts['bookly'] ) {
			return 'repository';
		}
		if ( (int) $counts['mapper'] < (int) $counts['repository'] ) {
			return 'mapper';
		}
		if ( (int) $counts['provider'] < (int) $counts['mapper'] ) {
			return 'provider';
		}
		return 'none';
	}

	/**
	 * Human-readable report for one diagnostic run.
	 *
	 * @param array<string, mixed> $report
	 * @return string
	 */
	public function format_pipeline_summary( array $report ) {
		$counts = isset( $report['counts'] ) && is_array( $report['counts'] ) ? $report['counts'] : array();
		$lines  = array();

		$lines[] = 'CAD staff pipeline report';
		$lines[] = 'Repository build: ' . (string) ( $report['repository_build'] ?? 'unknown' );
		if ( ! empty( $report['error'] ) ) {
			$lines[] = 'ERROR: ' . (string) $report['error'];
		}
		$lines[] = '';
		$lines[] = 'Bookly resources: ' . (int) ( $counts['bookly'] ?? 0 );
		$lines[] = 'Repository:       ' . (int) ( $counts['repository'] ?? 0 );
		$lines[] = 'Mapper:           ' . (int) ( $counts['mapper'] ?? 0 );
		$lines[] = 'Provider:         ' . (int) ( $counts['provider'] ?? 0 );
		$lines[] = 'AJAX tables:      ' . (int) ( $counts['ajax'] ?? 0 );
		if ( array_key_exists( 'ui', $counts ) && null !== $counts['ui'] ) {
			$lines[] = 'UI columns:       ' . (int) $counts['ui'];
		} else {
			$lines[] = 'UI columns:       (pending browser)';
		}

		$failing = (string) ( $report['failingLayer'] ?? 'none' );
		$lines[] = '';

		if ( 'none' === $failing && ( null === ( $counts['ui'] ?? null ) || (int) $counts['ui'] === (int) ( $counts['ajax'] ?? 0 ) ) ) {
			$lines[] = 'No backend mismatch detected.';
			if ( null !== ( $counts['ui'] ?? null ) && (int) $counts['ui'] === (int) ( $counts['ajax'] ?? 0 ) ) {
				$lines[] = 'AJAX and UI column counts match.';
			}
		} else {
			if ( 'none' !== $failing ) {
				$lines[] = 'Mismatch detected at: ' . strtoupper( $failing );
			}
			if ( null !== ( $counts['ui'] ?? null ) && (int) $counts['ui'] !== (int) ( $counts['ajax'] ?? 0 ) ) {
				$lines[] = 'Mismatch detected between AJAX and UI.';
				$failing = 'ui';
			}
		}

		$excluded = isset( $report['excluded'] ) && is_array( $report['excluded'] ) ? $report['excluded'] : array();

		if ( 'repository' === $failing && ! empty( $excluded['repository'] ) ) {
			$lines[] = '';
			$lines[] = 'Missing from Repository (present in Bookly):';
			foreach ( $excluded['repository'] as $row ) {
				$lines[] = $this->format_resource_line( $row );
			}
			$reasons = array();
			foreach ( $excluded['repository'] as $row ) {
				$reason = isset( $row['excludedReason'] ) ? (string) $row['excludedReason'] : 'unknown';
				$reasons[ $reason ] = true;
			}
			$lines[] = '';
			$lines[] = 'Excluded because: ' . implode( '; ', array_keys( $reasons ) );
		}

		if ( 'mapper' === $failing && ! empty( $excluded['mapper'] ) ) {
			$lines[] = '';
			$lines[] = 'Missing from Mapper:';
			foreach ( $excluded['mapper'] as $row ) {
				$lines[] = $this->format_resource_line( $row );
			}
		}

		if ( 'provider' === $failing && ! empty( $excluded['provider'] ) ) {
			$lines[] = '';
			$lines[] = 'Missing from Provider (cad_scheduler_tables filter):';
			foreach ( $excluded['provider'] as $row ) {
				$lines[] = $this->format_resource_line( $row );
			}
			if ( ! empty( $report['tables_filter_registered'] ) ) {
				$lines[] = '';
				$lines[] = 'Note: a cad_scheduler_tables filter is registered on this site.';
			}
		}

		if ( 'ui' === $failing ) {
			$lines[] = '';
			$lines[] = 'AJAX returned resources that the grid did not render as columns.';
			$lines[] = 'Check horizontal scroll, CDN asset version, and Config.tables after merge.';
			if ( ! empty( $report['stages']['ajax'] ) && is_array( $report['stages']['ajax'] ) ) {
				$lines[] = '';
				$lines[] = 'AJAX tables:';
				foreach ( $report['stages']['ajax'] as $row ) {
					$lines[] = $this->format_resource_line( $row );
				}
			}
		}

		$lines[] = '';
		$lines[] = 'Stage details (id | name | position | visibility | archived):';
		foreach ( array( 'bookly', 'repository', 'mapper', 'provider' ) as $stage ) {
			$lines[] = '';
			$lines[] = strtoupper( $stage ) . ' (' . (int) ( $counts[ $stage ] ?? 0 ) . ')';
			$rows = isset( $report['stages'][ $stage ] ) && is_array( $report['stages'][ $stage ] )
				? $report['stages'][ $stage ]
				: array();
			if ( ! $rows ) {
				$lines[] = '  (none)';
				continue;
			}
			foreach ( $rows as $row ) {
				$lines[] = '  ' . $this->format_resource_line( $row, false );
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param array<string, mixed> $row
	 * @param bool                 $bullet
	 */
	private function format_resource_line( array $row, $bullet = true ) {
		$id       = (string) ( $row['id'] ?? '' );
		$name     = (string) ( $row['name'] ?? '' );
		$position = array_key_exists( 'position', $row ) && null !== $row['position'] ? (string) $row['position'] : '—';
		$vis      = (string) ( $row['visibility'] ?? '' );
		$archived = ! empty( $row['archived'] ) ? 'true' : 'false';
		$prefix   = $bullet ? '- ' : '';
		$line     = "{$prefix}{$name} (ID {$id}) | position={$position} | visibility={$vis} | archived={$archived}";
		if ( ! empty( $row['excludedReason'] ) ) {
			$line .= ' | reason: ' . (string) $row['excludedReason'];
		}
		return $line;
	}

	public function get_schedule( $date ) {
		$appointments = $this->mapper->map_appointments(
			$this->repository->get_appointments_for_date( $date )
		);

		/**
		 * Filter the full normalized appointments list for a date.
		 *
		 * @param array  $appointments Normalized CAD appointments.
		 * @param string $date         Y-m-d.
		 */
		$appointments = apply_filters( 'cad_scheduler_appointments', $appointments, $date );

		$payload = array(
			'date'         => $date,
			'appointments' => is_array( $appointments ) ? $appointments : array(),
			'tables'       => $this->get_tables(),
		);

		if ( function_exists( 'cad_scheduler_diagnostics_enabled' ) && cad_scheduler_diagnostics_enabled() ) {
			$payload['staffPipeline'] = $this->debug_tables_pipeline();
		}

		return $payload;
	}

	/**
	 * @param string $appointment_id
	 * @param string $status
	 * @return bool
	 */
	public function update_appointment_status( $appointment_id, $status ) {
		$result = $this->repository->update_appointment_status( $appointment_id, $status );
		return false !== $result;
	}

	/**
	 * Reschedule an appointment via Bookly's admin save path
	 * (`Bookly\Lib\Utils\Appointment::checkTime` + `::save`).
	 *
	 * Updates staff (table) and start time; preserves stored duration
	 * (`end_date - start_date`). Does not overwrite conflicting slots.
	 *
	 * @param string|int      $appointment_id Bookly appointment id.
	 * @param string|int      $staff_id       Target Bookly staff / CAD table id.
	 * @param string          $start_date     WP-local `Y-m-d H:i:s` or ISO-8601.
	 * @param string|null     $end_date       Optional; when null, duration is preserved.
	 * @return array{
	 *   ok: bool,
	 *   code?: string,
	 *   message?: string,
	 *   appointment?: array|null,
	 *   conflicts?: array,
	 *   errors?: array,
	 *   bookly?: array
	 * }
	 */
	public function update_appointment( $appointment_id, $staff_id, $start_date, $end_date = null ) {
		$appointment_id = (int) $appointment_id;
		$staff_id       = (int) $staff_id;

		if ( $appointment_id <= 0 ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_appointment',
				'message' => 'Invalid appointment.',
			);
		}
		if ( $staff_id <= 0 ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_staff',
				'message' => 'Invalid table / staff.',
			);
		}

		$start_mysql = $this->normalize_bookly_datetime( $start_date );
		if ( ! $start_mysql ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_start',
				'message' => 'Invalid start time.',
			);
		}

		$entity_class = '\Bookly\Lib\Entities\Appointment';
		$utils_class  = '\Bookly\Lib\Utils\Appointment';
		if ( ! class_exists( $entity_class ) ) {
			return array(
				'ok'      => false,
				'code'    => 'bookly_unavailable',
				'message' => 'Missing Bookly class: ' . $entity_class,
			);
		}
		if ( ! class_exists( $utils_class ) ) {
			return array(
				'ok'      => false,
				'code'    => 'bookly_unavailable',
				'message' => 'Missing Bookly class: ' . $utils_class,
			);
		}

		$appointment = new \Bookly\Lib\Entities\Appointment();
		if ( ! $appointment->load( $appointment_id ) ) {
			return array(
				'ok'      => false,
				'code'    => 'not_found',
				'message' => 'Appointment not found.',
			);
		}

		$old_start = $appointment->getStartDate();
		$old_end   = $appointment->getEndDate();
		if ( ! $old_start || ! $old_end ) {
			return array(
				'ok'      => false,
				'code'    => 'missing_times',
				'message' => 'Appointment has no start/end time.',
			);
		}

		if ( null !== $end_date && '' !== (string) $end_date ) {
			$end_mysql = $this->normalize_bookly_datetime( $end_date );
			if ( ! $end_mysql ) {
				return array(
					'ok'      => false,
					'code'    => 'invalid_end',
					'message' => 'Invalid end time.',
				);
			}
		} else {
			$duration = max( 60, strtotime( $old_end ) - strtotime( $old_start ) );
			try {
				$start_dt  = new DateTimeImmutable( $start_mysql, wp_timezone() );
				$end_mysql = $start_dt->modify( '+' . $duration . ' seconds' )->format( 'Y-m-d H:i:s' );
			} catch ( Exception $e ) {
				return array(
					'ok'      => false,
					'code'    => 'invalid_start',
					'message' => 'Invalid start time.',
				);
			}
		}

		if ( strtotime( $end_mysql ) <= strtotime( $start_mysql ) ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_interval',
				'message' => 'End time must be after start time.',
			);
		}

		$customers = $this->customers_payload_for_save( $appointment );
		if ( empty( $customers ) ) {
			return array(
				'ok'      => false,
				'code'    => 'no_customers',
				'message' => 'Appointment has no customers to save.',
			);
		}

		$service_id = $appointment->getServiceId();
		$service_id = $service_id ? (int) $service_id : null;
		$location_id = $appointment->getLocationId() ? (int) $appointment->getLocationId() : 0;

		$check = \Bookly\Lib\Utils\Appointment::checkTime(
			$appointment_id,
			$start_mysql,
			$end_mysql,
			$staff_id,
			$service_id ? $service_id : 0,
			$location_id,
			$customers
		);

		if ( ! empty( $check['date_interval_not_available'] ) ) {
			return array(
				'ok'        => false,
				'code'      => 'conflict',
				'message'   => 'That time conflicts with another appointment on this table.',
				'conflicts' => is_array( $check ) ? $check : array(),
			);
		}

		/**
		 * Whether CAD reschedule should trigger Bookly notifications.
		 *
		 * @param bool   $notify
		 * @param int    $appointment_id
		 * @param string $start_mysql
		 * @param int    $staff_id
		 */
		$notify = (bool) apply_filters(
			'cad_scheduler_reschedule_notify',
			true,
			$appointment_id,
			$start_mysql,
			$staff_id
		);

		$custom_name  = $appointment->getCustomServiceName();
		$custom_price = $appointment->getCustomServicePrice();
		// Save without Bookly's deferred notification queue UI; send directly below when $notify.

		$bookly = \Bookly\Lib\Utils\Appointment::save(
			$appointment_id,
			$staff_id,
			null === $service_id ? null : $service_id,
			$custom_name,
			null === $custom_price || '' === $custom_price ? '' : $custom_price,
			$location_id,
			0,
			$start_mysql,
			$end_mysql,
			array( 'enabled' => false ),
			array(),
			'current',
			$customers,
			0,
			$appointment->getInternalNote(),
			'backend'
		);

		if ( empty( $bookly['success'] ) ) {
			$errors  = isset( $bookly['errors'] ) && is_array( $bookly['errors'] ) ? $bookly['errors'] : array();
			$message = 'Could not save appointment.';
			if ( ! empty( $errors['time_interval'] ) && is_string( $errors['time_interval'] ) ) {
				$message = $errors['time_interval'];
			} elseif ( ! empty( $errors['db'] ) && is_string( $errors['db'] ) ) {
				$message = $errors['db'];
			} elseif ( ! empty( $errors['overflow_capacity'] ) ) {
				$message = 'Not enough capacity for this table/service.';
			}

			return array(
				'ok'      => false,
				'code'    => 'save_failed',
				'message' => $message,
				'errors'  => $errors,
				'bookly'  => $bookly,
			);
		}

		if ( $notify
			&& class_exists( '\Bookly\Lib\Notifications\Booking\Sender', false )
			&& method_exists( '\Bookly\Lib\Notifications\Booking\Sender', 'sendForCA' )
		) {
			$saved = new \Bookly\Lib\Entities\Appointment();
			if ( $saved->load( $appointment_id ) ) {
				foreach ( $saved->getCustomerAppointments( true ) as $ca ) {
					\Bookly\Lib\Notifications\Booking\Sender::sendForCA( $ca, $saved, array(), true );
				}
			}
		}

		$row    = $this->repository->get_appointment_by_id( $appointment_id );
		$mapped = $row ? $this->mapper->map_appointment( $row ) : null;

		return array(
			'ok'          => true,
			'appointment' => $mapped,
			'conflicts'   => is_array( $check ) ? $check : array(),
			'bookly'      => array(
				'success'  => true,
				'notified' => $notify,
			),
		);
	}

	/**
	 * Create a Bookly appointment (Quick Add) via checkTime + save.
	 *
	 * @param array $args {
	 *   @type int|string $staff_id
	 *   @type string     $start
	 *   @type string     $end Optional.
	 *   @type int        $duration_minutes Default 90.
	 *   @type string     $customer_name
	 *   @type string     $phone
	 *   @type string     $email
	 *   @type int        $painters
	 *   @type string     $notes
	 *   @type int        $service_id Optional Bookly service id.
	 *   @type string     $internal_note
	 * }
	 * @return array
	 */
	public function create_appointment( array $args ) {
		$staff_id = (int) ( $args['staff_id'] ?? 0 );
		if ( $staff_id <= 0 ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_staff',
				'message' => 'Invalid table / staff.',
			);
		}

		$customer_name = trim( (string) ( $args['customer_name'] ?? '' ) );
		if ( '' === $customer_name ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_customer',
				'message' => 'Customer name is required.',
			);
		}

		$start_mysql = $this->normalize_bookly_datetime( (string) ( $args['start'] ?? '' ) );
		if ( ! $start_mysql ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_start',
				'message' => 'Invalid start time.',
			);
		}

		$end_raw = isset( $args['end'] ) ? (string) $args['end'] : '';
		if ( '' !== $end_raw ) {
			$end_mysql = $this->normalize_bookly_datetime( $end_raw );
			if ( ! $end_mysql ) {
				return array(
					'ok'      => false,
					'code'    => 'invalid_end',
					'message' => 'Invalid end time.',
				);
			}
		} else {
			$duration = (int) ( $args['duration_minutes'] ?? 90 );
			if ( $duration < 15 ) {
				$duration = 15;
			}
			try {
				$start_dt  = new DateTimeImmutable( $start_mysql, wp_timezone() );
				$end_mysql = $start_dt->modify( '+' . $duration . ' minutes' )->format( 'Y-m-d H:i:s' );
			} catch ( Exception $e ) {
				return array(
					'ok'      => false,
					'code'    => 'invalid_start',
					'message' => 'Invalid start time.',
				);
			}
		}

		if ( strtotime( $end_mysql ) <= strtotime( $start_mysql ) ) {
			return array(
				'ok'      => false,
				'code'    => 'invalid_interval',
				'message' => 'End time must be after start time.',
			);
		}

		$entity_class   = '\Bookly\Lib\Entities\Appointment';
		$utils_class    = '\Bookly\Lib\Utils\Appointment';
		$customer_class = '\Bookly\Lib\Entities\Customer';
		if ( ! class_exists( $entity_class ) || ! class_exists( $utils_class ) || ! class_exists( $customer_class ) ) {
			return array(
				'ok'      => false,
				'code'    => 'bookly_unavailable',
				'message' => 'Bookly appointment APIs are not available.',
			);
		}

		$phone    = trim( (string) ( $args['phone'] ?? '' ) );
		$email    = trim( (string) ( $args['email'] ?? '' ) );
		$painters = max( 1, (int) ( $args['painters'] ?? 1 ) );
		$notes    = (string) ( $args['notes'] ?? '' );
		$internal = (string) ( $args['internal_note'] ?? $notes );

		$customer_id = $this->ensure_bookly_customer( $customer_name, $phone, $email );
		if ( $customer_id <= 0 ) {
			return array(
				'ok'      => false,
				'code'    => 'customer_failed',
				'message' => 'Could not create Bookly customer.',
			);
		}

		$service_id = isset( $args['service_id'] ) ? (int) $args['service_id'] : 0;
		$service_id = (int) apply_filters( 'cad_scheduler_quick_add_service_id', $service_id, $args );
		$service_id = $service_id > 0 ? $service_id : null;

		$custom_name = (string) apply_filters(
			'cad_scheduler_quick_add_custom_service_name',
			'Studio Reservation',
			$args
		);
		if ( null !== $service_id ) {
			$custom_name = null;
		}

		$status = (string) apply_filters( 'cad_scheduler_quick_add_status', 'approved', $args );
		if ( '' === $status ) {
			$status = 'approved';
		}

		$customers = array(
			array(
				'id'                => $customer_id,
				'ca_id'             => '',
				'custom_fields'     => array(),
				'extras'            => array(),
				'number_of_persons' => $painters,
				'notes'             => $notes,
				'status'            => $status,
				'payment_id'        => null,
				'payment_action'    => '',
				'payment_for'       => 'current',
				'series_id'         => null,
				'timezone'          => null,
				'created_from'      => 'backend',
			),
		);

		$location_id = 0;
		$check       = \Bookly\Lib\Utils\Appointment::checkTime(
			0,
			$start_mysql,
			$end_mysql,
			$staff_id,
			$service_id ? $service_id : 0,
			$location_id,
			$customers
		);

		if ( ! empty( $check['date_interval_not_available'] ) ) {
			return array(
				'ok'        => false,
				'code'      => 'conflict',
				'message'   => 'That time conflicts with another appointment on this table.',
				'conflicts' => is_array( $check ) ? $check : array(),
			);
		}

		$notify = (bool) apply_filters( 'cad_scheduler_quick_add_notify', true, $args );

		$bookly = \Bookly\Lib\Utils\Appointment::save(
			0,
			$staff_id,
			$service_id,
			null === $custom_name ? null : $custom_name,
			'',
			$location_id,
			0,
			$start_mysql,
			$end_mysql,
			array( 'enabled' => false ),
			array(),
			'current',
			$customers,
			0,
			$internal,
			'backend'
		);

		if ( empty( $bookly['success'] ) ) {
			$errors  = isset( $bookly['errors'] ) && is_array( $bookly['errors'] ) ? $bookly['errors'] : array();
			$message = 'Could not create appointment.';
			if ( ! empty( $errors['time_interval'] ) && is_string( $errors['time_interval'] ) ) {
				$message = $errors['time_interval'];
			} elseif ( ! empty( $errors['db'] ) && is_string( $errors['db'] ) ) {
				$message = $errors['db'];
			} elseif ( ! empty( $errors['overflow_capacity'] ) ) {
				$message = 'Not enough capacity for this table/service.';
			}

			return array(
				'ok'      => false,
				'code'    => 'save_failed',
				'message' => $message,
				'errors'  => $errors,
				'bookly'  => $bookly,
			);
		}

		$new_id = 0;
		if ( ! empty( $bookly['appointment_id'] ) ) {
			$new_id = (int) $bookly['appointment_id'];
		} elseif ( ! empty( $bookly['id'] ) ) {
			$new_id = (int) $bookly['id'];
		}

		if ( $new_id <= 0 ) {
			global $wpdb;
			$new_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}bookly_appointments WHERE staff_id = %d AND start_date = %s ORDER BY id DESC LIMIT 1",
					$staff_id,
					$start_mysql
				)
			);
		}

		if ( $new_id > 0
			&& $notify
			&& class_exists( '\Bookly\Lib\Notifications\Booking\Sender', false )
			&& method_exists( '\Bookly\Lib\Notifications\Booking\Sender', 'sendForCA' )
		) {
			$saved = new \Bookly\Lib\Entities\Appointment();
			if ( $saved->load( $new_id ) ) {
				foreach ( $saved->getCustomerAppointments( true ) as $ca ) {
					\Bookly\Lib\Notifications\Booking\Sender::sendForCA( $ca, $saved, array(), true );
				}
			}
		}

		$row    = $new_id > 0 ? $this->repository->get_appointment_by_id( $new_id ) : null;
		$mapped = $row ? $this->mapper->map_appointment( $row ) : null;

		return array(
			'ok'          => true,
			'appointment' => $mapped,
			'conflicts'   => is_array( $check ) ? $check : array(),
			'bookly'      => array(
				'success'        => true,
				'notified'       => $notify,
				'appointment_id' => $new_id,
			),
		);
	}

	/**
	 * Find or create a Bookly customer by phone/email/name.
	 *
	 * @param string $full_name
	 * @param string $phone
	 * @param string $email
	 * @return int
	 */
	private function ensure_bookly_customer( $full_name, $phone = '', $email = '' ) {
		$customer_class = '\Bookly\Lib\Entities\Customer';
		if ( ! class_exists( $customer_class ) ) {
			return 0;
		}

		$existing_id = 0;
		if ( '' !== $phone || '' !== $email ) {
			global $wpdb;
			$table = $wpdb->prefix . 'bookly_customers';
			if ( '' !== $phone ) {
				$existing_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare( "SELECT id FROM {$table} WHERE phone = %s ORDER BY id DESC LIMIT 1", $phone )
				);
			}
			if ( $existing_id <= 0 && '' !== $email ) {
				$existing_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s ORDER BY id DESC LIMIT 1", $email )
				);
			}
		}

		if ( $existing_id > 0 ) {
			$customer = new \Bookly\Lib\Entities\Customer();
			if ( $customer->load( $existing_id ) ) {
				return $existing_id;
			}
		}

		$parts      = preg_split( '/\s+/', trim( $full_name ), 2 );
		$first_name = is_array( $parts ) && isset( $parts[0] ) ? $parts[0] : $full_name;
		$last_name  = is_array( $parts ) && isset( $parts[1] ) ? $parts[1] : '';

		$customer = new \Bookly\Lib\Entities\Customer();
		if ( method_exists( $customer, 'setFullName' ) ) {
			$customer->setFullName( $full_name );
		}
		if ( method_exists( $customer, 'setFirstName' ) ) {
			$customer->setFirstName( $first_name );
		}
		if ( method_exists( $customer, 'setLastName' ) ) {
			$customer->setLastName( $last_name );
		}
		if ( method_exists( $customer, 'setPhone' ) ) {
			$customer->setPhone( $phone );
		}
		if ( method_exists( $customer, 'setEmail' ) ) {
			$customer->setEmail( $email );
		}

		$saved = $customer->save();
		if ( false === $saved ) {
			return 0;
		}

		return (int) $customer->getId();
	}

	/**
	 * Build Bookly save() customer rows from existing customer appointments.
	 *
	 * @param \Bookly\Lib\Entities\Appointment $appointment
	 * @return array<int, array<string, mixed>>
	 */
	private function customers_payload_for_save( $appointment ) {
		$customers = array();
		$cas       = $appointment->getCustomerAppointments( true );
		if ( ! is_array( $cas ) || empty( $cas ) ) {
			return array();
		}

		foreach ( $cas as $ca ) {
			$custom_fields = json_decode( (string) $ca->getCustomFields(), true );
			$extras        = json_decode( (string) $ca->getExtras(), true );
			if ( ! is_array( $custom_fields ) ) {
				$custom_fields = array();
			}
			if ( ! is_array( $extras ) ) {
				$extras = array();
			}

			$timezone = null;
			if ( class_exists( '\Bookly\Lib\Proxy\Pro', false )
				&& method_exists( '\Bookly\Lib\Proxy\Pro', 'getCustomerTimezone' )
			) {
				$timezone = \Bookly\Lib\Proxy\Pro::getCustomerTimezone(
					$ca->getTimeZone(),
					$ca->getTimeZoneOffset()
				);
			}

			$customers[] = array(
				'id'                => (int) $ca->getCustomerId(),
				'ca_id'             => (int) $ca->getId(),
				'custom_fields'     => $custom_fields,
				'extras'            => $extras,
				'number_of_persons' => (int) $ca->getNumberOfPersons(),
				'notes'             => (string) $ca->getNotes(),
				'status'            => (string) $ca->getStatus(),
				'payment_id'        => $ca->getPaymentId(),
				'payment_action'    => '',
				'payment_for'       => 'current',
				'series_id'         => $ca->getSeriesId(),
				'timezone'          => $timezone,
				'created_from'      => 'backend',
			);
		}

		return $customers;
	}

	/**
	 * Normalize a client datetime to Bookly/WP-local `Y-m-d H:i:s`.
	 *
	 * @param string $value
	 * @return string|null
	 */
	private function normalize_bookly_datetime( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return null;
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?/', $value ) ) {
			$value = str_replace( 'T', ' ', $value );
			// Strip trailing timezone designator for plain local strings.
			if ( preg_match( '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2})(:\d{2})?/', $value, $m )
				&& ! preg_match( '/[Zz]|[+-]\d{2}:?\d{2}$/', trim( $value ) )
			) {
				$base = $m[1] . ( isset( $m[2] ) && $m[2] ? $m[2] : ':00' );
				return $base;
			}
		}

		try {
			$dt = new DateTimeImmutable( $value );
			return $dt->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return null;
		}
	}
}
