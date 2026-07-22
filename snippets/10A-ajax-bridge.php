<?php
/**
 * Snippet 10A — AJAX Bridge
 *
 * Enqueues CAD Scheduler scripts and registers WordPress AJAX handlers
 * for Bookly schedule data.
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register and enqueue frontend assets.
 */
function cad_enqueue_assets() {
	$base = get_stylesheet_directory_uri() . '/cad-scheduler/src/';

	wp_enqueue_script( 'cad-core', $base . 'cad-core.js', array(), '0.1.0', true );
	wp_enqueue_script( 'cad-components', $base . 'cad-components.js', array( 'cad-core' ), '0.1.0', true );
	wp_enqueue_script( 'cad-editor', $base . 'cad-editor.js', array( 'cad-core' ), '0.1.0', true );
	wp_enqueue_script( 'cad-calendar', $base . 'cad-calendar.js', array( 'cad-core', 'cad-components' ), '0.1.0', true );
	wp_enqueue_script( 'cad-ui', $base . 'cad-ui.js', array( 'cad-core', 'cad-calendar' ), '0.1.0', true );

	wp_localize_script(
		'cad-core',
		'cadConfig',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'cad_scheduler' ),
			'tables'  => apply_filters( 'cad_scheduler_tables', array() ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'cad_enqueue_assets' );

/**
 * Fetch schedule for a given date.
 */
function cad_ajax_get_schedule() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );

	$date = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );

	// TODO: Query Bookly appointments for $date and map to CAD format.
	wp_send_json_success(
		array(
			'date'         => $date,
			'appointments' => array(),
		)
	);
}
add_action( 'wp_ajax_cad_get_schedule', 'cad_ajax_get_schedule' );
add_action( 'wp_ajax_nopriv_cad_get_schedule', 'cad_ajax_get_schedule' );

/**
 * Update a single appointment.
 */
function cad_ajax_update_appointment() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );

	// TODO: Validate and persist changes via Bookly API.
	wp_send_json_success( array( 'updated' => true ) );
}
add_action( 'wp_ajax_cad_update_appointment', 'cad_ajax_update_appointment' );
