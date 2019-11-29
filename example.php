<?php

require_once 'vendor/autoload.php';

$videoPareser = new \YandexVideoParser\Serp();
$dom = $videoPareser->load('сериал', 0);
$res = $videoPareser->parse($dom);
print_r($videoPareser->getErrors());
foreach ($res as $video) {
    print_r($video);
}