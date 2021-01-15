<?php

namespace YandexVideoParser;

use GuzzleHttp\Client as HttpClient;
use PHPHtmlParser\Dom;

class YandexVideoParser
{

    private $guzzle;

    public function __construct()
    {
        $this->guzzle = new HttpClient(['allow_redirects' => true]);
    }

    public function load(string $query, int $page = 0): Dom
    {
        $request = '{"blocks":[{"block":"serp-list_infinite_yes","params":{},"version":2}]}';
        $url = 'https://yandex.ru/video/search?format=json&request=' . urlencode($request) . '&rdrnd=' . rand(1, 999999) . '&p=' . $page . '&text=' . urlencode($query);
        $httpResponse = $this->guzzle->get($url);
        $jsonResponse = json_decode($httpResponse->getBody(), false, 512, JSON_THROW_ON_ERROR);
        if (empty($jsonResponse->blocks)) {
            throw new \Exception('Blocks is empty');
        }
        $block = current($jsonResponse->blocks);
        if (!isset($block->html)) {
            throw new \Exception('Html not found');
        }
        return (new Dom())->loadStr($block->html);
    }

    public function parse(Dom $dom): array
    {
        $res = [];
        foreach ($dom->root->find('div[data-video]') as $element) {
            $videoData = json_decode(htmlspecialchars_decode($element->getAttribute('data-video')), false, 512, JSON_THROW_ON_ERROR);
            if (!isset($videoData->url)) {
                throw new \Exception('Url not found');
            } else if (!isset($videoData->pos) || !is_numeric($videoData->pos)) {
                throw new \Exception('Position not found or not numeric');
            } else if (!isset($videoData->greenHost)) {
                throw new \Exception('GreenHost not found');
            } else if (!isset($videoData->hosting)) {
                throw new \Exception('Hosting not found');
            } else if (!isset($videoData->playerId)) {
                throw new \Exception('PlayerId not found');
            } else if (!isset($videoData->id) || !is_numeric($videoData->id)) {
                throw new \Exception('Id not found or not numeric');
            } else if (!isset($videoData->title)) {
                throw new \Exception('Title not found');
            } else if (!isset($videoData->player->noAutoplayHtml)) {
                throw new \Exception('NoAutoplayHtml not found');
            } else if (!isset($videoData->counters->toHostingLoaded->stredParams->duration) || !is_numeric($videoData->id)) {
                throw new \Exception('Duration not found or not numeric');
            }
            $iframeElement = (new Dom())->loadStr($videoData->player->noAutoplayHtml)->root->find('iframe[src]');
            if ($iframeElement->getIterator()->count() == 0) {
                throw new \Exception('Iframe not found');
            }
            $iframe = $iframeElement->getIterator()->current()->getAttribute('src');
            $thumbs = $element->find('img[class=thumb-image__image]');
            if ($thumbs->count() == 0) {
                $thumb2 = $element->find('div[class=serp-item__preview serp-item__preview_rounded]');
                if ($thumb2->count() != 0 && preg_match('/background\-image: url\((.*)\)/', $thumb2->getIterator()->current()->getAttribute('style'), $matches)) {
                    $thumb = $matches[1];
                } else {
                    throw new \Exception('Thumbnail not found');
                }
            } else {
                $thumb = $thumbs->getIterator()->current()->getAttribute('src');
            }
            $res[] = [
                'id' => (int)$videoData->id,
                'position' => $videoData->pos,
                'url' => $videoData->url,
                'title' => $videoData->title,
                'greenHost' => $videoData->greenHost,
                'hosting' => $videoData->hosting,
                'playerId' => $videoData->playerId,
                'iframe' => $iframe,
                'thumb' => $thumb,
                'duration' => (int)$videoData->counters->toHostingLoaded->stredParams->duration
            ];
        }
        return $res;
    }

}
