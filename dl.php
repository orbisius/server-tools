<?php

// Replace this link with the download link to the large file
// then access this file e.g. http://example.com/dl.php
$url = "http://archive.ubuntu.com/ubuntu/dists/xenial/main/installer-amd64/current/images/netboot/mini.iso";

// The code is fine but use it at your own risk!
// If you (ab)use it too much your hosting company will contact you because it
// stresses the network and server's hard drives.
// Blog post link: https://orbisius.com/4246

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$dir = __DIR__;

if (!function_exists('shell_exec')) {
	die("Error: The hosting company doesn't allow external program execution. Can't proceed.");
}

if (!is_writable($dir)) {
	die("Error: current directory is not writeable. Can't proceed.");
}

$res = '';
$file = $url;
$file = trim($file);
$file = basename($file); // only filename
$file = preg_replace('#\?.*#si', '', $file); // no params/query string info
$file = preg_replace('#\.\.+#si', '', $file); // no ..
$file = trim($file, '/' ); // just in case

$url = trim($url);
$uniq_id = 'orb_dl_' . sha1($url);
$log_file = "$dir/$uniq_id.log";
$target_file = "$dir/" . (empty($file) ? $uniq_id : $file);

echo "<pre>";

if (file_exists($log_file)) {
	$res .= "There's a download for this file.";
	$res .= file_get_contents($log_file);
} else {
	$url_esc = escapeshellarg( $url );
	$log_file_esc = escapeshellarg($log_file);
	$target_file_esc = escapeshellarg($target_file);
	$res .= "will save the file in [$target_file]\n";
	// options:
	// -b continue in background
	// -c resume download (if it was interrupted before)
	// -d debug log
	// -O target file
	// 2>&1 redirects error messages
	$res = `wget -O $target_file_esc -bcd $url_esc -o $log_file_esc 2>&1`;
}

echo $res;

echo "</pre>";

exit(0);
