<?php

/*
 * This tool allows you to export all of the databases in separate files and compress them using gzip.
 * php db.php db_root_user db_root_pass output_dir
 * 
 * php db.php "" "" C:\Copy\Dropbox\cloud\db_export
 * @author Svetoslav Marinov (Slavi) | orbisius.com
 * @copyright 2016
 */
$user = empty( $argv[1] ) ? 'root' : $argv[1];
$pass = empty( $argv[2] ) ? '' : $argv[2];
$out_dir = empty( $argv[3] ) ? dirname( __FILE__ ) . '/db_export/' . date( 'Y-m-d/' ) : $argv[3];
$out_dir = rtrim( $out_dir, '/\\');
$out_dir .= "/";

if ( ! is_dir( $out_dir ) ) {
    if ( ! mkdir( $out_dir, 0755, 1 ) ) {
        echo "Error: cannot create db export dir: [$out_dir]\n";
        exit(255);
    }
}

$mysql = 'mysql';
$mysqldump = 'mysqldump';

$db_user_esc = escapeshellarg( $user );
$db_pass_esc = escapeshellarg( $pass );

echo "Starting at: " . date('r') . "\n";

$db_list = `$mysql --user=$db_user_esc --password=$db_pass_esc -Ns -e "SHOW DATABASES" 2>&1`;

if ( preg_match( '#error|fail|fatal#si', $db_list ) ) {
    echo "Error: " . $db_list . "\n";
    exit(255);
}

$db_names = preg_split( '#[\r\n]+#si', $db_list );
$db_names = array_filter( $db_names );
$db_names = array_unique( $db_names );
sort( $db_names );

echo "Loaded databases at " . date('r') . "\n";
echo "Total databases: " . count( $db_names ) . "\n";

foreach ( $db_names as $db_name ) {
    $db_name_esc = escapeshellarg( $db_name );

    // .sql
    $db_name_sql_file = $out_dir . $db_name . '.sql';
    $db_name_sql_file_esc = escapeshellarg( $db_name_sql_file );

    // .log
    $db_name_log_file = $out_dir . $db_name . '.log';
    $db_name_log_file_esc = escapeshellarg( $db_name_log_file );

    echo "Processing: [$db_name]\n";
    echo "mysql dump\n";
    echo `$mysqldump --opt --user=$db_user_esc --password=$db_pass_esc --single-transaction --hex-blob --complete-insert --default-character-set=utf8 $db_name_esc > $db_name_sql_file 2>$db_name_log_file`;

    // If error log file exists but nothing is there don't keep it.
    if ( file_exists( $db_name_log_file )
            && filesize( $db_name_log_file ) < 5 ) {
        unlink( $db_name_log_file );
    }

    // gzip
    if ( file_exists( $db_name_sql_file ) ) {
        echo "gziping [$db_name_sql_file_esc]\n";
        echo `gzip -f -9 $db_name_sql_file_esc`; // -f override
    }

    //break;
}

echo "Finished at " . date('r') . "\n";

exit(0);
