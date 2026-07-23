<?php
/**
 * Snippet 10A — AJAX Bridge (v2)
 *
 * Code Snippets: priority 20 — bootstrap / routing only.
 * Requires snippets 10–12 from includes/ (see docs/deployment.md).
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CAD_SCHEDULER_VERSION' ) ) {
	define( 'CAD_SCHEDULER_VERSION', '2.0.0' );
}

if ( ! defined( 'CAD_SCHEDULER_GITHUB_REPO' ) ) {
	define( 'CAD_SCHEDULER_GITHUB_REPO', 'CAD-Support/cad-scheduler' );
}

function cad_scheduler_asset_url( $path ) {
	$path = ltrim( $path, '/' );
	$url  = sprintf(
		'https://cdn.jsdelivr.net/gh/%s@%s/%s',
		CAD_SCHEDULER_GITHUB_REPO,
		CAD_SCHEDULER_VERSION,
		$path
	);
	return apply_filters( 'cad_scheduler_asset_url', $url, $path );
}

function cad_scheduler_ready() {
	return class_exists( 'CAD_Schedule_Provider', false );
}

function cad_schedule_provider() {
	static $provider = null;
	if ( ! cad_scheduler_ready() ) {
		return null;
	}
	if ( null === $provider ) {
		$provider = new CAD_Schedule_Provider();
	}
	return $provider;
}

function cad_enqueue_assets() {
	if ( ! cad_scheduler_ready() ) {
		return;
	}

	$provider = cad_schedule_provider();
	$ver      = CAD_SCHEDULER_VERSION;
	$src      = cad_scheduler_asset_url( 'src/' );

	wp_enqueue_style( 'cad-scheduler', cad_scheduler_asset_url( 'assets/css/cad-scheduler.css' ), array(), $ver );
	wp_enqueue_script( 'cad-core', $src . 'cad-core.js', array(), $ver, true );
	wp_enqueue_script( 'cad-api', $src . 'cad-api.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-components', $src . 'cad-components.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-editor', $src . 'cad-editor.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-calendar', $src . 'cad-calendar.js', array( 'cad-core', 'cad-components' ), $ver, true );
	wp_enqueue_script( 'cad-ui', $src . 'cad-ui.js', array( 'cad-core', 'cad-api', 'cad-calendar' ), $ver, true );

	wp_localize_script(
		'cad-core',
		'cadConfig',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'cad_scheduler' ),
			'tables'      => $provider->get_tables(),
			'dayStart'    => '08:00',
			'dayEnd'      => '20:00',
			'slotMinutes' => 15,
			'hourHeight'  => 64,
		)
	);

	wp_add_inline_script(
		'cad-ui',
		"(function(){document.addEventListener('DOMContentLoaded',function(){if(typeof CAD==='undefined'||!CAD.ui)return;CAD.init(window.cadConfig||{});var m=document.getElementById('cad-scheduler');if(m)CAD.ui.mount('#cad-scheduler').load(new Date().toISOString().slice(0,10));});})();"
	);
}

function cad_maybe_enqueue() {
	if ( ! cad_scheduler_ready() || ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( $post && has_shortcode( $post->post_content, 'cad_scheduler' ) ) {
		cad_enqueue_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'cad_maybe_enqueue' );

function cad_scheduler_shortcode() {
	if ( ! cad_scheduler_ready() ) {
		return '';
	}
	cad_enqueue_assets();
	return '<div id="cad-scheduler" class="cad-scheduler-mount"></div>';
}
add_shortcode( 'cad_scheduler', 'cad_scheduler_shortcode' );

function cad_ajax_get_schedule() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );
	$provider = cad_schedule_provider();
	if ( ! $provider ) {
		wp_send_json_error( array( 'message' => 'CAD modules not loaded.' ), 500 );
	}
	$date = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		wp_send_json_error( array( 'message' => 'Invalid date.' ), 400 );
	}
	wp_send_json_success( $provider->get_schedule( $date ) );
}
add_action( 'wp_ajax_cad_get_schedule', 'cad_ajax_get_schedule' );
add_action( 'wp_ajax_nopriv_cad_get_schedule', 'cad_ajax_get_schedule' );

function cad_ajax_update_appointment() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );
	wp_send_json_success( array( 'updated' => true ) );
}
add_action( 'wp_ajax_cad_update_appointment', 'cad_ajax_update_appointment' );
