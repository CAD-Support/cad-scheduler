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

		return array(
			'date'         => $date,
			'appointments' => is_array( $appointments ) ? $appointments : array(),
		);
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
