<?php
/**
 * CAD Scheduler — Schedule Provider
 *
 * Code Snippets: priority 12 — paste entire file.
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
		return array(
			'date'         => $date,
			'appointments' => $this->mapper->map_appointments(
				$this->repository->get_appointments_for_date( $date )
			),
		);
	}
}
