#!/usr/bin/env php
<?php
$message = stream_get_contents(STDIN);
if ($message === false || $message === '') {
    exit;
}
$sock = fsockopen('localhost', 1025, $errno, $errstr, 5);
if ($sock) {
    fwrite($sock, $message);
    fclose($sock);
}