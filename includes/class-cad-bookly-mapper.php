<?php
/**
 * CAD Scheduler — Bookly Mapper
 *
 * Code Snippets: priority 11 — paste entire file.
 *
 * Translates Bookly rows into the CAD normalized appointment model.
 * That model is the public API between backend and frontend — evolve it
 * additively (new properties / nests). Do not rename or remove existing keys
 * without a coordinated frontend migration.
 *
 * Custom-field ID → name mapping stays here — never exposed to the UI.
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'CAD_Bookly_Mapper', false ) ) {
	return;
}

class CAD_Bookly_Mapper {

	/**
	 * Map Bookly staff rows to CAD table columns.
	 * Preserves repository order (Bookly position). Output is { id, name } only.
	 *
	 * @param array $rows Staff rows from the repository.
	 * @return array<int, array{id: string, name: string}>
	 */
	public function map_staff_tables( array $rows ) {
		$tables = array();
		foreach ( $rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$tables[] = array(
				'id'   => $id,
				'name' => (string) ( $row['full_name'] ?? '' ),
			);
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
				$dt  = new DateTimeImmutable( $start );
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

		$phone_raw = trim( (string) ( $row['customer_phone'] ?? '' ) );
		$phone     = '' === $phone_raw ? null : $phone_raw;
		$service   = (string) ( $row['service_title'] ?? '' );
		$service_id = isset( $row['service_id'] ) && '' !== (string) $row['service_id']
			? (string) $row['service_id']
			: null;

		$field_values = $this->decode_custom_fields(
			$row['custom_fields_json'] ?? ( $row['custom_fields'] ?? ( $row['json_data'] ?? '' ) )
		);
		$birthday     = $this->map_birthday_fields( $field_values );
		$type         = $this->resolve_type( $service, $service_id, $birthday, $row );

		// Contract: core keys always present; nests are type-exclusive; no Bookly field IDs.
		// Public API — prefer additive changes; do not rename/remove keys lightly.
		$appointment = array(
			'id'        => $id,
			'tableId'   => $table_id,
			'type'      => $type,
			'start'     => $start,
			'end'       => $end,
			'customer'  => (string) ( $row['customer_name'] ?? '' ),
			'phone'     => $phone,
			'service'   => $service,
			'serviceId' => $service_id,
			'status'    => (string) ( $row['appointment_status'] ?? '' ),
			'painters'  => $painters,
			'notes'     => (string) ( $row['internal_note'] ?? '' ),
			'birthday'  => ( 'birthday' === $type ) ? $birthday : null,
			'studio'    => ( 'studio' === $type ) ? new stdClass() : null,
			'event'     => ( 'event' === $type ) ? new stdClass() : null,
		);

		/**
		 * Filter a single normalized CAD appointment (no raw Bookly field IDs).
		 *
		 * @param array $appointment Normalized appointment.
		 * @param array $row         Raw repository row (for advanced filters only).
		 */
		return apply_filters( 'cad_scheduler_appointment', $appointment, $row );
	}

	/**
	 * Bookly custom-field ID registry (PHP only).
	 *
	 * @return array<string, array<string, int|string>>
	 */
	public function custom_field_map() {
		$defaults = array(
			'birthday' => array(
				'childName' => 79073,
				'age'       => 84803,
				'package'   => 76858,
			),
		);

		/**
		 * Filter semantic custom-field maps. Values are Bookly field IDs.
		 *
		 * @param array $defaults Default ID map.
		 */
		$map = apply_filters( 'cad_scheduler_custom_field_map', $defaults );
		return is_array( $map ) ? $map : $defaults;
	}

	/**
	 * Decode Bookly custom_fields / json_data into id => value (string keys).
	 *
	 * Supports:
	 * - [{"id":79073,"value":"Emma"}, ...]
	 * - {"79073":"Emma", ...}
	 * - PHP serialized arrays of the above shapes
	 *
	 * @param mixed $raw JSON string, array, or empty.
	 * @return array<string, string>
	 */
	public function decode_custom_fields( $raw ) {
		if ( null === $raw || '' === $raw ) {
			return array();
		}

		$data = $raw;
		if ( is_string( $raw ) ) {
			$trimmed = trim( $raw );
			if ( '' === $trimmed ) {
				return array();
			}
			$decoded = json_decode( $trimmed, true );
			if ( JSON_ERROR_NONE === json_last_error() && null !== $decoded ) {
				$data = $decoded;
			} elseif ( function_exists( 'maybe_unserialize' ) ) {
				$data = maybe_unserialize( $trimmed );
			} else {
				return array();
			}
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		$out = array();

		// List of { id, value } objects.
		$is_list = array_keys( $data ) === range( 0, count( $data ) - 1 );
		if ( $is_list ) {
			foreach ( $data as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$fid = isset( $item['id'] ) ? (string) $item['id'] : '';
				if ( '' === $fid ) {
					continue;
				}
				$out[ $fid ] = $this->stringify_field_value( $item['value'] ?? '' );
			}
			return $out;
		}

		// Associative id => value.
		foreach ( $data as $fid => $value ) {
			if ( is_array( $value ) && array_key_exists( 'value', $value ) ) {
				$out[ (string) ( $value['id'] ?? $fid ) ] = $this->stringify_field_value( $value['value'] );
				continue;
			}
			$out[ (string) $fid ] = $this->stringify_field_value( $value );
		}

		return $out;
	}

	/**
	 * @param array<string, string> $field_values Decoded custom fields.
	 * @return array{childName: string, age: string, package: string}|null
	 */
	private function map_birthday_fields( array $field_values ) {
		$map = $this->custom_field_map();
		$bday_map = isset( $map['birthday'] ) && is_array( $map['birthday'] ) ? $map['birthday'] : array();

		$child = $this->value_for_mapped_id( $field_values, $bday_map['childName'] ?? null );
		$age   = $this->value_for_mapped_id( $field_values, $bday_map['age'] ?? null );
		$pkg   = $this->value_for_mapped_id( $field_values, $bday_map['package'] ?? null );

		if ( '' === $child && '' === $age && '' === $pkg ) {
			return null;
		}

		// Named keys only — never expose Bookly numeric field IDs to the UI.
		return array(
			'childName' => $child,
			'age'       => $age,
			'package'   => $pkg,
		);
	}

	/**
	 * @param array       $field_values Decoded fields.
	 * @param int|string|null $field_id Bookly field id.
	 */
	private function value_for_mapped_id( array $field_values, $field_id ) {
		if ( null === $field_id || '' === $field_id ) {
			return '';
		}
		$key = (string) $field_id;
		return isset( $field_values[ $key ] ) ? trim( (string) $field_values[ $key ] ) : '';
	}

	/**
	 * @param string     $service_title Service title.
	 * @param string|null $service_id   Service id.
	 * @param array|null $birthday      Mapped birthday nest or null.
	 * @param array      $row           Raw row (filters may use).
	 * @return string studio|birthday|event
	 */
	private function resolve_type( $service_title, $service_id, $birthday, array $row ) {
		$default = 'studio';
		if ( is_array( $birthday ) ) {
			$default = 'birthday';
		} elseif ( preg_match( '/birthday/i', (string) $service_title ) ) {
			$default = 'birthday';
		}

		/**
		 * Filter appointment type for renderer selection.
		 *
		 * @param string      $type         studio|birthday|event
		 * @param string      $service_title
		 * @param string|null $service_id
		 * @param array|null  $birthday
		 * @param array       $row
		 */
		$type = apply_filters( 'cad_scheduler_appointment_type', $default, $service_title, $service_id, $birthday, $row );
		$type = is_string( $type ) ? strtolower( trim( $type ) ) : $default;
		if ( ! in_array( $type, array( 'studio', 'birthday', 'event' ), true ) ) {
			$type = $default;
		}
		return $type;
	}

	private function stringify_field_value( $value ) {
		if ( is_array( $value ) ) {
			// Bookly checkbox / multi sometimes stores arrays.
			$flat = array_filter( array_map( 'strval', $value ), 'strlen' );
			return implode( ', ', $flat );
		}
		if ( is_bool( $value ) ) {
			return $value ? '1' : '';
		}
		if ( is_scalar( $value ) ) {
			return trim( (string) $value );
		}
		return '';
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
