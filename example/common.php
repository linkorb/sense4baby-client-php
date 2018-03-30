<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new \Dotenv\Dotenv(__DIR__ . '/..');
$dotenv->load();

$username = getenv('SENSE4BABY_USERNAME');
$password = getenv('SENSE4BABY_PASSWORD');
$deviceId = getenv('SENSE4BABY_DEVICE_ID');

if (!$username || !$password || !$deviceId) {
    exit("Please setup the environment variables\n");
}

$client = \Sense4Baby\Client::fromCredentials($username, $password, $deviceId);
