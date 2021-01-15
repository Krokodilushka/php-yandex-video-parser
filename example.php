<?php

use YandexVideoParser\YandexVideoParser;

require_once 'vendor/autoload.php';

$videoPareser = new YandexVideoParser();
$dom = $videoPareser->load('Игра престолов 8 сезон 6 серия финал 20.05.2019', 0);
$res = $videoPareser->parse($dom);
print_r($res);
