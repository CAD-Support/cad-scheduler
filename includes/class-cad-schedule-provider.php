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
}
