<?php
/* 
 * This is a cool git sync tool. It uses linux's find command to find all git folders and do a pull for each.
 * This assumes that each repo *knows* its git credentials.
 * This tool runs right away when called from the command line.
 * If it's accessed web the browser pass: ?smart_git_sync=1 parameter
 *
 * @author Svetoslav Marinov (Slavi) | http://orbisius.com
 * @copyright 2016
 */

// Need the param to run.
if ( php_sapi_name() != 'cli' && ! isset( $_REQUEST['smart_git_sync'] ) ) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 300');//300 seconds
    die( 'Service Temporarily Unavailable or missing start param.' );
}

if ( php_sapi_name() != 'cli' ) {
    echo "<pre>";
}

ignore_user_abort( true );
set_time_limit( 30 * 60 );
$dirs = array();

if ( defined( 'ABSPATH' ) ) { // WP root dir
    $dirs[] = ABSPATH;
}

if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) { // WP root dir
    $dirs[] = $_SERVER['DOCUMENT_ROOT'];
}

$dirs[] = dirname( __FILE__ );
$dirs[] = dirname( __FILE__ ) . '/wp-content/';
sort( $dirs );

// The shortest/rootest should be at the top.
$dir = array_shift( $dirs );
$dir = defined( 'ABSPATH' ) ? ABSPATH : dirname( __FILE__ );

echo "Looking for git repos starting from: [$dir]\n";
$git_dirs_lines = `find $dir -name .git -type d`;
$git_dirs_lines = str_replace( '.git', '', $git_dirs_lines );

$git_dirs = preg_split( '#[\r\n]+#si', $git_dirs_lines );
$git_dirs = array_filter( $git_dirs );
$git_dirs = array_unique( $git_dirs );
sort( $git_dirs );

echo "Found: " . count ( $git_dirs ) . " git dirs\n";
echo "Dirs: \n" . join ("\n", $git_dirs ) . "\n";

foreach ( $git_dirs as $git_dir ) {
    echo "Syncing [$git_dir]\n";
    chdir( $git_dir );
    echo `git pull 2>&1`;
}

if ( php_sapi_name() != 'cli' ) {
    echo "</pre>";
}

exit(0);
