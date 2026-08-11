#!/usr/bin/env php
<?php
$message = stream_get_contents(STDIN);
if ($message === false || $message === '') {
    file_put_contents('/tmp/mailwrapper.log', date('c') . " empty stdin\n", FILE_APPEND);
    exit;
}
file_put_contents('/tmp/mailwrapper.log', date('c') . " got " . strlen($message) . " bytes\n", FILE_APPEND);
$sock = @fsockopen('localhost', 1025, $errno, $errstr, 5);
if (!$sock) {
    file_put_contents('/tmp/mailwrapper.log', date('c') . " socket failed: $errno $errstr\n", FILE_APPEND);
    exit;
}
fwrite($sock, $message);
fclose($sock);
file_put_contents('/tmp/mailwrapper.log', date('c') . " sent\n", FILE_APPEND);