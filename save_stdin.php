#!/usr/bin/env php
<?php

/**
 * This saves the STDIN output into a file.
 * @author Svetoslav Marinov | http://orbisius.com
 * @see https://stackoverflow.com/questions/14187993/save-large-files-from-php-stdin
 * @see http://www.gregfreeman.io/2013/processing-data-with-php-using-stdin-and-piping/
 */

try {
    $exit_code = 0; // success by default
    $bytes_read = 0;

    if (php_sapi_name() == 'cli') {
        $in = fopen('php://stdin', 'rb');
    } else {
        $in = fopen('php://input', 'rb');
    }

    if (empty($in)) {
        throw new Exception("Cannot open STDIN for reading.");
    }

    $output_file = empty($argv[1]) ? __DIR__ . '/aaa_stdin_data_capture_' . microtime(true) . '.' . mt_rand(9999, 99999) . '.log' : $argv[1];

    $out = fopen($output_file, 'wb');

    if (empty($out)) {
        throw new Exception("Cannot open output file for writing.");
    }

    $lock_status = flock($out, LOCK_EX); // blocking?

    if (empty($lock_status)) {
        throw new Exception("Cannot lock output file for writing.");
    }

    while (!feof($in)) {
        $buff = fread($in, 8192);
        fwrite($out, $buff);
        $bytes_read += strlen($buff);
    }

    flock($out, LOCK_UN);
    fclose($in);
    fclose($out);
    fwrite(STDERR, "STDERR: Output file: [$output_file]\n");
    fwrite(STDERR, "STDERR: Bytes read: [$bytes_read]\n");
} catch (Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    $exit_code = 255;
}

exit($exit_code);
