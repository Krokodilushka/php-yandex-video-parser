<?php

use YandexVideoParser\YandexVideoParser;

require_once 'vendor/autoload.php';

$videoPareser = new YandexVideoParser();
$dom = $videoPareser->load('сериал', 0);
$res = $videoPareser->parse($dom);
print_r($res);
