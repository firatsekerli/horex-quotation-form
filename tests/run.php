<?php
/**
 * Run every test file in this directory.
 *
 * Usage:  php tests/run.php
 *
 * Each suite runs in its own process, so one suite's stubbed state cannot leak into
 * the next.
 *
 * @package Horex
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$failed = 0;

foreach ( glob( __DIR__ . '/test-*.php' ) as $suite ) {
	printf( "\n=== %s ===\n", basename( $suite ) );

	passthru( sprintf( '%s %s', escapeshellarg( PHP_BINARY ), escapeshellarg( $suite ) ), $status );

	if ( 0 !== $status ) {
		$failed++;
	}
}

printf( "\n%s\n", $failed ? "{$failed} suite(s) failed" : 'All suites passed' );

exit( $failed ? 1 : 0 );
