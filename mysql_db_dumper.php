<?php

/*
 * This tool allows you to export all of the databases in separate files and compress them using gzip.
 * php mysql_db_dumper.php db_root_user db_root_pass output_dir
 * 
 * php mysql_db_dumper.php "" "" C:\Copy\Dropbox\cloud\db_export
 *
 * The output dir can contain placeholders e.g. YYYY-MM-DD or DAY_OF_WEEK which are dynamically replaced which are nice for weekly backups.
 * php "C:\Copy\Dropbox\cloud\projects\default\htdocs\github\orbisius-github-projects\server-tools\mysql_db_dumper.php" "root" "root_db_pass" C:\Copy\Dropbox\cloud\db_export\YYYY-MM\DAY_OF_WEEK\
 *
 * @author Svetoslav Marinov (Slavi) | orbisius.com
 * @copyright 2016
 */
$user = empty( $argv[1] ) ? 'root' : $argv[1];
$pass = empty( $argv[2] ) ? '' : $argv[2];
$out_dir = empty( $argv[3] ) ? dirname( __FILE__ ) . '/db_export/YYYY-MM-DD/' : $argv[3];
$out_dir = rtrim( $out_dir, '/\\');
$out_dir .= "/";

// If the path or export dir contains something that looks like YYYY-MM-DD it will be automatically
// replaced with the current date.
if ( preg_match( '#YYYY\-MM\-DD#si', $out_dir ) ) {
    // The order is super important!
	$out_dir = preg_replace( '#\bYYYY\-MM\-DD\b#si', date( 'Y-m-d' ), $out_dir );
	$out_dir = preg_replace( '#\bYYYY\-MM\b#si', date( 'Y-m' ), $out_dir );
	$out_dir = preg_replace( '#\bYYYY\b#si', date( 'Y' ), $out_dir );
}

// This is good if you want to have a weekly backup
// $0 root pass c:\backups\db_export\YYYY-MM\DAY_OF_WEEK\ ->
$day_of_week_regex = '#WEEK[_\-]DAY|DAY[_\-]OF[_\-](?:the[_\-]?)?WEEK#si';

if ( preg_match( $day_of_week_regex, $out_dir ) ) {
    $out_dir = preg_replace( $day_of_week_regex, strtolower( date( 'l' ) ), $out_dir );
}

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
echo "Export dir: [$out_dir]\n";

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
