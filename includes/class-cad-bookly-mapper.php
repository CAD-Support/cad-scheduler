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
	 * Preserves repository order (Bookly position).
	 *
	 * @param array $rows Staff rows from the repository.
	 * @return array<int, array{id: string, name: string, capacity: int}>
	 */
	public function map_staff_tables( array $rows ) {
		$capacities       = $this->staff_capacity_map();
		$default_capacity = (int) apply_filters( 'cad_scheduler_default_table_capacity', 8 );
		if ( $default_capacity < 1 ) {
			$default_capacity = 8;
		}

		$tables = array();
		foreach ( $rows as $row ) {
			$id = (string) ( $row['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$capacity = isset( $capacities[ $id ] ) ? (int) $capacities[ $id ] : $default_capacity;
			$capacity = (int) apply_filters( 'cad_scheduler_table_capacity', $capacity, $id, $row );
			if ( $capacity < 1 ) {
				$capacity = $default_capacity;
			}
			$tables[] = array(
				'id'       => $id,
				'name'     => (string) ( $row['full_name'] ?? '' ),
				'capacity' => $capacity,
			);
		}
		return $tables;
	}

	/**
	 * Max Group Booking capacity per staff from Bookly staff_services (when present).
	 *
	 * @return array<string, int>
	 */
	private function staff_capacity_map() {
		global $wpdb;

		$table = $wpdb->prefix . 'bookly_staff_services';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			"SELECT staff_id, MAX(capacity_max) AS capacity_max
			FROM {$table}
			WHERE capacity_max IS NOT NULL AND capacity_max > 0
			GROUP BY staff_id",
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$id  = (string) ( $row['staff_id'] ?? '' );
			$cap = (int) ( $row['capacity_max'] ?? 0 );
			if ( '' !== $id && $cap > 0 ) {
				$out[ $id ] = $cap;
			}
		}
		return $out;
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
				$dt  = new DateTimeImmutable( $start, wp_timezone() );
				$end = $duration > 0
					? $dt->add( new DateInterval( 'PT' . $duration . 'S' ) )->format( 'Y-m-d H:i:s' )
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
		$email_raw = trim( (string) ( $row['customer_email'] ?? '' ) );
		$email     = '' === $email_raw ? null : $email_raw;
		$service   = (string) ( $row['service_title'] ?? '' );
		$service_id = isset( $row['service_id'] ) && '' !== (string) $row['service_id']
			? (string) $row['service_id']
			: null;

		$field_values = $this->decode_custom_fields(
			$row['custom_fields_json'] ?? ( $row['custom_fields'] ?? ( $row['json_data'] ?? '' ) )
		);
		$birthday     = $this->map_birthday_fields( $field_values );
		$type         = $this->resolve_type( $service, $service_id, $birthday, $row );

		$first = trim( (string) ( $row['customer_first_name'] ?? '' ) );
		$last  = trim( (string) ( $row['customer_last_name'] ?? '' ) );
		if ( '' === $first && '' === $last ) {
			$parts = preg_split( '/\s+/', trim( (string) ( $row['customer_name'] ?? '' ) ), 2 );
			$first = is_array( $parts ) && isset( $parts[0] ) ? $parts[0] : '';
			$last  = is_array( $parts ) && isset( $parts[1] ) ? $parts[1] : '';
		}

		// Contract: core keys always present; nests are type-exclusive; no Bookly field IDs on list payload.
		// Public API — prefer additive changes; do not rename/remove keys lightly.
		$appointment = array(
			'id'             => $id,
			'tableId'        => $table_id,
			'type'           => $type,
			'start'          => $start,
			'end'            => $end,
			'customer'       => (string) ( $row['customer_name'] ?? '' ),
			'customerId'     => isset( $row['customer_id'] ) && '' !== (string) $row['customer_id']
				? (string) $row['customer_id']
				: null,
			'customerFirst'  => $first,
			'customerLast'   => $last,
			'phone'          => $phone,
			'email'          => $email,
			'service'        => $service,
			'serviceId'      => $service_id,
			'color'          => $this->normalize_hex_color( $row['service_color'] ?? ( $row['color'] ?? null ) ),
			'status'         => (string) ( $row['appointment_status'] ?? '' ),
			'painters'       => $painters,
			'notes'          => (string) ( $row['internal_note'] ?? '' ),
			'customerNotes'  => (string) ( $row['customer_notes'] ?? '' ),
			'birthday'       => ( 'birthday' === $type ) ? $birthday : null,
			'studio'         => ( 'studio' === $type ) ? new stdClass() : null,
			'event'          => ( 'event' === $type ) ? new stdClass() : null,
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
	 * Dynamic Reservation Details fields for an appointment row (service-aware).
	 *
	 * Labels/types come from Bookly Custom Fields definitions when available.
	 * The Reservation Manager renders this list — it should not hard-code service types.
	 *
	 * @param array $row Raw appointment row (from get_appointment_by_id).
	 * @param array $definitions Optional preloaded field definitions.
	 * @return array<int, array{id: string, label: string, type: string, value: string, required: bool}>
	 */
	public function map_detail_fields( array $row, array $definitions = array() ) {
		$values = $this->decode_custom_fields(
			$row['custom_fields_json'] ?? ( $row['custom_fields'] ?? ( $row['json_data'] ?? '' ) )
		);
		$service_id = isset( $row['service_id'] ) ? (string) $row['service_id'] : '';

		if ( empty( $definitions ) ) {
			$definitions = $this->fallback_detail_definitions( $row, $values );
		}

		$fields = array();
		foreach ( $definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$fid = (string) ( $def['id'] ?? '' );
			if ( '' === $fid ) {
				continue;
			}
			$raw_type = strtolower( trim( (string) ( $def['type'] ?? 'text' ) ) );
			// Staff admin UI — never surface website/CAPTCHA fields.
			if ( in_array( $raw_type, array( 'captcha', 'captcha_v2', 'recaptcha', 'text-content' ), true ) ) {
				continue;
			}
			$services = isset( $def['services'] ) && is_array( $def['services'] ) ? $def['services'] : array();
			// Empty services list = available for all services.
			if ( ! empty( $services ) && '' !== $service_id && ! in_array( $service_id, $services, true ) ) {
				continue;
			}
			$fields[] = array(
				'id'       => $fid,
				'label'    => (string) ( $def['label'] ?? ( 'Field ' . $fid ) ),
				'type'     => $this->normalize_field_input_type( (string) ( $def['type'] ?? 'text' ) ),
				'value'    => isset( $values[ $fid ] ) ? (string) $values[ $fid ] : '',
				'required' => ! empty( $def['required'] ),
			);
		}

		/**
		 * Filter detail fields for Reservation Manager.
		 * Add/remove fields per site without changing the UI.
		 *
		 * @param array $fields
		 * @param array $row
		 * @param array $values Decoded id=>value map.
		 */
		$filtered = apply_filters( 'cad_scheduler_reservation_detail_fields', $fields, $row, $values );
		return is_array( $filtered ) ? array_values( $filtered ) : $fields;
	}

	/**
	 * When Bookly custom-field definitions are unavailable, build a label list
	 * from the semantic ID map (still not hard-coded in the JS manager).
	 *
	 * @param array $row
	 * @param array $values
	 * @return array<int, array<string, mixed>>
	 */
	private function fallback_detail_definitions( array $row, array $values ) {
		$map   = $this->custom_field_map();
		$defs  = array();
		$labels = array(
			'childName'         => 'Child Name',
			'age'               => 'Age',
			'package'           => 'Birthday Package',
			'guests'            => 'Guests',
			'nonPainters'       => 'Non-Painters',
			'specialOccasion'   => 'Special Occasion',
			'appointmentNotes'  => 'Appointment Notes',
		);

		foreach ( $map as $group => $fields ) {
			if ( ! is_array( $fields ) ) {
				continue;
			}
			foreach ( $fields as $key => $fid ) {
				if ( null === $fid || '' === $fid || ! $fid ) {
					continue;
				}
				$defs[] = array(
					'id'       => (string) $fid,
					'label'    => $labels[ $key ] ?? ucwords( preg_replace( '/([A-Z])/', ' $1', (string) $key ) ),
					'type'     => 'text',
					'services' => array(),
					'required' => false,
					'group'    => (string) $group,
				);
			}
		}

		/**
		 * Fallback detail field definitions when Bookly option is empty.
		 *
		 * @param array $defs
		 * @param array $row
		 * @param array $values
		 */
		$filtered = apply_filters( 'cad_scheduler_reservation_detail_field_fallback', $defs, $row, $values );
		return is_array( $filtered ) ? $filtered : $defs;
	}

	/**
	 * @param string $type Bookly custom field type.
	 * @return string text|textarea|number|email|tel|select
	 */
	private function normalize_field_input_type( $type ) {
		$type = strtolower( trim( $type ) );
		$map  = array(
			'text'         => 'text',
			'textarea'     => 'textarea',
			'text-content' => 'textarea',
			'number'       => 'number',
			'numeric'      => 'number',
			'email'        => 'email',
			'tel'          => 'tel',
			'phone'        => 'tel',
			'select'       => 'text',
			'checkboxes'   => 'text',
			'radio'        => 'text',
			'radiobuttons' => 'text',
		);
		return isset( $map[ $type ] ) ? $map[ $type ] : 'text';
	}

	/**
	 * Bookly custom-field ID registry (PHP only).
	 *
	 * @return array<string, array<string, int|string>>
	 */
	public function custom_field_map() {
		$defaults = array(
			'birthday' => array(
				'childName'   => 79073,
				'age'         => 84803,
				'package'     => 76858,
				'guests'      => 0,
				'nonPainters' => 0,
			),
			'studio'   => array(
				'specialOccasion'  => 0,
				'appointmentNotes' => 0,
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

	/**
	 * Normalize Bookly service colour to #RRGGBB or null.
	 *
	 * @param mixed $color
	 * @return string|null
	 */
	private function normalize_hex_color( $color ) {
		if ( null === $color || false === $color || '' === $color ) {
			return null;
		}
		$raw = trim( (string) $color );
		if ( '' === $raw ) {
			return null;
		}
		if ( '#' !== $raw[0] ) {
			$raw = '#' . $raw;
		}
		if ( preg_match( '/^#([0-9A-Fa-f]{3})$/', $raw, $m ) ) {
			$h = $m[1];
			return strtoupper( '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2] );
		}
		if ( preg_match( '/^#([0-9A-Fa-f]{6})$/', $raw ) ) {
			return strtoupper( $raw );
		}
		return null;
	}

	/**
	 * Bookly stores studio wall-clock datetimes (not absolute UTC instants).
	 * Emit `Y-m-d H:i:s` with no timezone offset so the browser cannot re-zone them.
	 *
	 * @param string $value MySQL datetime or similar.
	 * @return string|null
	 */
	private function iso( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$value = trim( $value );
		// Prefer digit extraction so offsets / "Z" / "T" never reach the client.
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(:\d{2})?/', $value, $m ) ) {
			return $m[1] . ' ' . $m[2] . ( ! empty( $m[3] ) ? $m[3] : ':00' );
		}

		try {
			return ( new DateTimeImmutable( $value, wp_timezone() ) )->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return null;
		}
	}
}
