<?php
/**
 * Offline verification: staff → table mapping preserves Bookly order;
 * archived rows never become columns. No name allowlists.
 *
 * Run: php docs/fixtures/verify-staff-tables.php
 *
 * @package CAD_Scheduler
 */

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$rest ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames
		return $value;
	}
}
if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone() {
		return new DateTimeZone( 'America/Toronto' );
	}
}
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__, 2 ) . '/includes/class-cad-bookly-mapper.php';

$mapper   = new CAD_Bookly_Mapper();
$failures = 0;

function assert_true( $cond, $label ) {
	global $failures;
	if ( $cond ) {
		echo "OK  {$label}\n";
	} else {
		echo "BAD {$label}\n";
		$failures++;
	}
}

// Simulate repository output already filtered to non-archive, ordered by position.
$rows = array(
	array( 'id' => '10', 'full_name' => 'Table 1', 'position' => 1, 'visibility' => 'public' ),
	array( 'id' => '11', 'full_name' => 'Party Space', 'position' => 2, 'visibility' => 'public' ),
	array( 'id' => '12', 'full_name' => 'Outside Studio', 'position' => 3, 'visibility' => 'private' ),
	array( 'id' => '13', 'full_name' => 'Table 2', 'position' => 4, 'visibility' => 'public' ),
);

$tables = $mapper->map_staff_tables( $rows );
assert_true( count( $tables ) === 4, 'maps all non-archive rows' );
assert_true( $tables[0]['name'] === 'Table 1', 'order position 1' );
assert_true( $tables[1]['name'] === 'Party Space', 'Party Space present in Bookly order' );
assert_true( $tables[2]['name'] === 'Outside Studio', 'Outside Studio present in Bookly order' );
assert_true( $tables[3]['name'] === 'Table 2', 'order position 4' );

foreach ( $tables as $t ) {
	assert_true( isset( $t['id'], $t['name'] ), 'table has id+name only keys present' );
	assert_true( ! array_key_exists( 'visibility', $t ), 'visibility not leaked to frontend' );
	assert_true( ! array_key_exists( 'position', $t ), 'position not leaked to frontend' );
}

// Empty name still creates a column (resource exists in Bookly).
$empty_name = $mapper->map_staff_tables(
	array( array( 'id' => '99', 'full_name' => '', 'position' => 1, 'visibility' => 'public' ) )
);
assert_true( count( $empty_name ) === 1 && $empty_name[0]['id'] === '99', 'empty name still maps' );

// No hardcoded allowlist: arbitrary future resource name.
$future = $mapper->map_staff_tables(
	array( array( 'id' => '50', 'full_name' => 'Future Resource X', 'position' => 1, 'visibility' => 'public' ) )
);
assert_true( $future[0]['name'] === 'Future Resource X', 'future resource name passes through' );

echo $failures ? "FAILED {$failures}\n" : "ALL PASSED\n";
exit( $failures ? 1 : 0 );
