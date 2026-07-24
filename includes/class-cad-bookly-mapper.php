<?php
/**
 * CAD Scheduler — Bookly Mapper
 *
 * Code Snippets: priority 11 — paste entire file.
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'CAD_Bookly_Mapper', false ) ) {
	return;
}

class CAD_Bookly_Mapper {

	public function map_staff_tables( array $rows ) {
		$tables = array();
		foreach ( $rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );
			if ( $id ) {
				$tables[] = array(
					'id'   => $id,
					'name' => (string) ( $row['full_name'] ?? '' ),
				);
			}
		}
		return $tables;
	}

	public function map_appointments( array $rows ) {
		$out = array();
		foreach ( $rows as $row ) {
			$mapped = $this->map_appointment( $row );
			if ( $mapped ) {
				$out[] = $mapped;
			}
		}
		return $out;
	}

	public function map_appointment( array $row ) {
		$id       = (string) ( $row['id'] ?? '' );
		$table_id = (string) ( $row['staff_id'] ?? '' );
		$start    = $this->iso( $row['start_date'] ?? '' );

		if ( ! $id || ! $table_id || ! $start ) {
			return null;
		}

		$end = $this->iso( $row['end_date'] ?? '' );
		if ( ! $end ) {
			$duration = (int) ( $row['service_duration'] ?? 0 );
			try {
				$dt = new DateTimeImmutable( $start );
				$end = $duration > 0
					? $dt->add( new DateInterval( 'PT' . $duration . 'S' ) )->format( DateTimeInterface::ATOM )
					: $start;
			} catch ( Exception $e ) {
				$end = $start;
			}
		}

		$painters = (int) ( $row['number_of_persons'] ?? 1 );
		if ( $painters < 1 ) {
			$painters = 1;
		}

		$payment_status = strtolower( (string) ( $row['payment_status'] ?? '' ) );

		return array(
			'id'       => $id,
			'tableId'  => $table_id,
			'start'    => $start,
			'end'      => $end,
			'customer' => (string) ( $row['customer_name'] ?? '' ),
			'service'  => (string) ( $row['service_title'] ?? '' ),
			'status'   => (string) ( $row['appointment_status'] ?? '' ),
			'painters' => $painters,
			'paid'     => ( 'completed' === $payment_status ),
			'notes'    => (string) ( $row['internal_note'] ?? '' ),
		);
	}

	private function iso( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}
		try {
			return ( new DateTimeImmutable( $value, wp_timezone() ) )->format( DateTimeInterface::ATOM );
		} catch ( Exception $e ) {
			return null;
		}
	}
}
