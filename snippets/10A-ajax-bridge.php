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

/**
 * @return array<int, array{code: string, message: string, fix?: string, blocking?: bool}>
 */
function cad_scheduler_health() {
	$issues = array();

	if ( ! class_exists( 'CAD_Bookly_Repository', false ) ) {
		$issues[] = array(
			'code'      => 'missing_repository',
			'message'   => 'CAD Bookly Repository is not loaded.',
			'fix'       => 'Activate snippet 10 from includes/class-cad-bookly-repository.php.',
			'blocking'  => true,
		);
	}

	if ( ! class_exists( 'CAD_Bookly_Mapper', false ) ) {
		$issues[] = array(
			'code'      => 'missing_mapper',
			'message'   => 'CAD Bookly Mapper is not loaded.',
			'fix'       => 'Activate snippet 11 from includes/class-cad-bookly-mapper.php.',
			'blocking'  => true,
		);
	}

	if ( ! class_exists( 'CAD_Schedule_Provider', false ) ) {
		$issues[] = array(
			'code'      => 'missing_provider',
			'message'   => 'CAD Schedule Provider is not loaded.',
			'fix'       => 'Activate snippet 12 from includes/class-cad-schedule-provider.php.',
			'blocking'  => true,
		);
	}

	if ( class_exists( 'CAD_Bookly_Repository', false ) && ! CAD_Bookly_Repository::is_available() ) {
		$issues[] = array(
			'code'     => 'bookly_unavailable',
			'message'  => 'Bookly database tables were not found.',
			'fix'      => 'Install and activate Bookly on this WordPress site.',
			'blocking' => false,
		);
	}

	return apply_filters( 'cad_scheduler_health', $issues );
}

/**
 * @param array<int, array{code?: string, message?: string, fix?: string, blocking?: bool}> $issues
 */
function cad_scheduler_has_blocking_issues( $issues = null ) {
	if ( null === $issues ) {
		$issues = cad_scheduler_health();
	}
	foreach ( $issues as $issue ) {
		if ( ! empty( $issue['blocking'] ) ) {
			return true;
		}
	}
	return false;
}

function cad_scheduler_diagnostics_enabled() {
	if ( cad_scheduler_has_blocking_issues() ) {
		return true;
	}
	return (bool) apply_filters( 'cad_scheduler_diagnostics_enabled', false );
}

/**
 * @param array<int, array{code?: string, message?: string, fix?: string}> $issues
 */
function cad_scheduler_render_diagnostics( array $issues ) {
	if ( empty( $issues ) ) {
		if ( ! cad_scheduler_diagnostics_enabled() ) {
			return '';
		}
		$issues = array(
			array(
				'code'    => 'ok',
				'message' => 'CAD Scheduler components are loaded.',
			),
		);
	}

	$html  = '<div class="cad-scheduler__diagnostics" role="alert">';
	$html .= '<p class="cad-scheduler__diagnostics-title"><strong>CAD Scheduler</strong></p>';
	$html .= '<ul class="cad-scheduler__diagnostics-list">';

	foreach ( $issues as $issue ) {
		$message = esc_html( (string) ( $issue['message'] ?? 'Unknown issue.' ) );
		$fix     = isset( $issue['fix'] ) ? esc_html( (string) $issue['fix'] ) : '';
		$html   .= '<li>' . $message . ( $fix ? ' — ' . $fix : '' ) . '</li>';
	}

	$html .= '</ul></div>';

	return $html;
}

/**
 * @return array<int, array{code: string, message: string}>
 */
function cad_scheduler_health_for_config() {
	$out = array();
	foreach ( cad_scheduler_health() as $issue ) {
		$out[] = array(
			'code'    => (string) ( $issue['code'] ?? 'unknown' ),
			'message' => (string) ( $issue['message'] ?? '' ),
		);
	}
	return $out;
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
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'cad_scheduler' ),
			'tables'       => $provider->get_tables(),
			'dayStart'     => '08:00',
			'dayEnd'       => '20:00',
			'slotMinutes'  => 15,
			'hourHeight'   => 64,
			'health'       => cad_scheduler_health_for_config(),
			'diagnostics'  => cad_scheduler_diagnostics_enabled(),
		)
	);

	wp_add_inline_script(
		'cad-ui',
		"(function(){document.addEventListener('DOMContentLoaded',function(){if(typeof CAD==='undefined'||!CAD.ui)return;CAD.init(window.cadConfig||{});var m=document.getElementById('cad-scheduler');if(!m)return;CAD.ui.mount('#cad-scheduler');if(m.querySelector('.cad-scheduler__diagnostics'))return;CAD.ui.load(new Date().toISOString().slice(0,10));});})();"
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
	$issues = cad_scheduler_health();

	if ( cad_scheduler_has_blocking_issues( $issues ) ) {
		return cad_scheduler_render_diagnostics( $issues );
	}

	cad_enqueue_assets();

	$html = '<div id="cad-scheduler" class="cad-scheduler-mount"></div>';

	if ( cad_scheduler_diagnostics_enabled() ) {
		$html .= cad_scheduler_render_diagnostics( $issues );
	}

	return $html;
}
add_shortcode( 'cad_scheduler', 'cad_scheduler_shortcode' );

function cad_ajax_get_schedule() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Authentication required.' ), 403 );
	}

	$capability = apply_filters( 'cad_scheduler_get_schedule_capability', 'read' );
	if ( ! current_user_can( $capability ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
	}

	$provider = cad_schedule_provider();
	if ( ! $provider ) {
		wp_send_json_error(
			array(
				'message' => 'CAD modules not loaded.',
				'health'  => cad_scheduler_health_for_config(),
			),
			500
		);
	}
	$date = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		wp_send_json_error( array( 'message' => 'Invalid date.' ), 400 );
	}
	wp_send_json_success( $provider->get_schedule( $date ) );
}
add_action( 'wp_ajax_cad_get_schedule', 'cad_ajax_get_schedule' );

function cad_ajax_update_appointment() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );
	wp_send_json_success( array( 'updated' => true ) );
}
add_action( 'wp_ajax_cad_update_appointment', 'cad_ajax_update_appointment' );
