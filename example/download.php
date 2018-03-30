<?php

require __DIR__ . '/common.php';

$res = $client->download('data/');
print_r($res);
exit("DONE" . PHP_EOL);