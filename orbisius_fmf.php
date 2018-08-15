<?php

/*
  This tool will show which posts that have featured images set but their local files are missing.
  It relies on the 'guid' field.
  requires: php 5.3+ and wp-cli

  (c) 2017 http://orbisius.com

  Usage:
  1) upload to wp dir and visit site.com/orbisius_fmf.php
  
  command line
   
  2) php orbisius_fmf.php -doutput_buffering=off > posts.html
  then open posts.html by going site.com/posts.html
 */

header( 'Content-type: text/html; charset=utf-8' );
header( 'X-Accel-Buffering: no' );

// In case this is run as a page.
set_time_limit(3600);
ob_implicit_flush(true);
@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
$php_cli = php_sapi_name() == 'cli';
$browser_job = ! $php_cli;
$prefix = $browser_job ? dirname( $_SERVER['REQUEST_URI']) : '';
$nl = "\n";

if ( $browser_job ) {
    $nl .= "<br/>";
    
    // Put something so the webservers don't buffer
    echo '<!-- some_empty_spaces: ' . str_repeat( ' ', 16 * 1024 ) . ' -->' . "$nl";
}

echo "Locating wp-cli ...$nl";

// paths to wp-cli
$wp_paths = [
    'wp',
    '/usr/local/bin/wp',
    '/usr/bin/wp',
    __DIR__ . '/../bin/wp',
];

$found = 0;
$wp_test = '';

foreach ($wp_paths as $wp) {
    $wp_test = trim(`$wp --info`);

    if (stripos($wp_test, 'WP-CLI root') !== false) {
        $found = 1;
        break;
    }
}

if (!$found) {
    die("wp-cli is missing. Can't continue.");
}

// http://stackoverflow.com/questions/4870697/php-flush-that-works-even-in-nginx
echo "wp cli found.$nl";

if ( $browser_job ) {
    echo "<pre>";
}

echo $wp_test;

if ( $browser_job ) {
    echo "</pre>";
}

echo $nl;
echo "Getting all pages & posts ...$nl";

// will return a list all post IDs separated by spaces
$ids_str = `$wp post list --post_type=post --format=ids`; //,page

// Cleanup
$ids_arr = preg_split('#\s+#si', $ids_str);
$ids_arr = array_unique($ids_arr);
$ids_arr = array_filter($ids_arr);
sort($ids_arr);
$cnt = count($ids_arr);

echo "Found " . $cnt . " posts.$nl";

foreach ($ids_arr as $idx => $id) {
    $id = (int) $id;
    echo "Checking ID: $id [" . ( $idx + 1 ) . " of $cnt]$nl";

    // https://runcommand.io/to/assign-featured-image-generated-post/
    $featured_img_id = `$wp post meta get $id _thumbnail_id`;
    $featured_img_id = trim($featured_img_id);
    $featured_img_id = (int) $featured_img_id;

    if (!empty($featured_img_id)) {
        // http://example.com/wp-content/uploads/2017/01/????-?????-e1483549644521.jpg
        $guid_esc = escapeshellarg( "guid" );
        $featured_img_url = `$wp post get $featured_img_id --field=$guid_esc`;
        $featured_img_url = trim($featured_img_url);

        if ( !empty($featured_img_url) 
                && preg_match('#(/wp-content/uploads/.*)#si', $featured_img_url, $matches)) {
            if (!file_exists(__DIR__ . $matches[1])) {
                if ( $browser_job ) {
                    echo "<div style='color:red'>";
                }
                echo "Error:$nl";
                echo "Missing thumbnail post [$id]$nl";
                echo "Edt URL: <a href='$prefix/wp-admin/post.php?post=$id&action=edit' target='_blank'>/wp-admin/post.php?post=$id&action=edit</a>$nl";
                echo "File is missing: " . $matches[1] . "$nl";
                echo "URL: " . $featured_img_url . "$nl";
                
                if ( $browser_job ) {
                    echo "</div>";
                }
            }
        }
    }

    if ($idx % 5 == 0) {
        @ob_flush();
        flush();
    }
}

echo "Done$nl";
exit(0);
