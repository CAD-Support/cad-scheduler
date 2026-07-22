<?php
/**
 * CAD Scheduler — Schedule Provider
 *
 * Code Snippets: create snippet "CAD — Schedule Provider", priority 12, run site-wide.
 * Plugin:        require_once CAD_SCHEDULER_DIR . 'includes/class-cad-schedule-provider.php';
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'CAD_Schedule_Provider', false ) ) {
	return;
}

/**
 * Coordinates Bookly queries and CAD response shaping.
 */
class CAD_Schedule_Provider {

	/** @var CAD_Bookly_Repository */
	private $repository;

	/** @var CAD_Bookly_Mapper */
	private $mapper;

	/**
	 * @param CAD_Bookly_Repository|null $repository Optional repository instance.
	 * @param CAD_Bookly_Mapper|null     $mapper     Optional mapper instance.
	 */
	public function __construct( CAD_Bookly_Repository $repository = null, CAD_Bookly_Mapper $mapper = null ) {
		$this->repository = $repository ?? new CAD_Bookly_Repository();
		$this->mapper     = $mapper ?? new CAD_Bookly_Mapper();
	}

	/**
	 * Retrieve CAD table definitions from Bookly staff records.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_tables() {
		$tables = $this->mapper->map_staff_tables(
			$this->repository->get_staff_tables()
		);

		/**
		 * Filter CAD table definitions before they reach the frontend.
		 *
		 * @param array<int, array<string, string>> $tables CAD table definitions.
		 */
		return apply_filters( 'cad_scheduler_tables', $tables );
	}

	/**
	 * Retrieve schedule data for a given date.
	 *
	 * @param string $date Date in YYYY-MM-DD format.
	 * @return array<string, mixed>
	 */
	public function get_schedule( $date ) {
		$appointments = $this->mapper->map_appointments(
			$this->repository->get_appointments_for_date( $date )
		);

		return array(
			'date'         => $date,
			'appointments' => $appointments,
		);
	}
}
