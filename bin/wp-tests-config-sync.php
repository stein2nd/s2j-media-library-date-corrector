<?php
/**
 * Sync DB_* defines in wp-tests-config.php from environment (see bin/phpunit-local.sh).
 */

$file = getenv( 'WP_TESTS_CONFIG_FILE' );
if ( ! is_string( $file ) || '' === $file || ! is_file( $file ) ) {
	exit( 0 );
}

$config = file_get_contents( $file );
if ( false === $config ) {
	fwrite( STDERR, "Could not read wp-tests-config.php.\n" );
	exit( 1 );
}

$replacements = array(
	'DB_NAME'     => (string) getenv( 'WP_TESTS_DB_NAME' ),
	'DB_USER'     => (string) getenv( 'WP_TESTS_DB_USER' ),
	'DB_PASSWORD' => (string) getenv( 'WP_TESTS_DB_PASSWORD' ),
	'DB_HOST'     => (string) getenv( 'WP_TESTS_DB_HOST' ),
);

foreach ( $replacements as $const => $value ) {
	if ( '' === $value ) {
		continue;
	}
	$pattern = "/define\\( '" . $const . "', '[^']*' \\);/";
	$replacement = "define( '" . $const . "', " . var_export( $value, true ) . ' );';
	$config = preg_replace( $pattern, $replacement, $config, 1, $count );
	if ( 0 === $count ) {
		fwrite( STDERR, "Could not update {$const} in wp-tests-config.php.\n" );
		exit( 1 );
	}
}

if ( false === file_put_contents( $file, $config ) ) {
	fwrite( STDERR, "Could not write wp-tests-config.php.\n" );
	exit( 1 );
}
