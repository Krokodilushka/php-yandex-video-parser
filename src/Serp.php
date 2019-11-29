<?php

namespace YandexVideoParser;

use GuzzleHttp\Client as HttpClient;
use PHPHtmlParser\Dom;

class Serp {

    private $guzzle;
    private $errors = [];

    public function __construct() {
        $this->guzzle = new HttpClient(['allow_redirects' => true]);
    }

    public function load(string $query, int $page = 1): Dom {
        $request = '{"blocks":[{"block":"serp-list_infinite_yes","params":{},"version":2}]}';
        $url = 'https://yandex.ru/video/search?format=json&request=' . urlencode($request) . '&rdrnd=' . rand(1, 999999) . '&p=' . $page . '&text=' . urlencode($query);
        $httpResponse = $this->guzzle->get($url);
        if ($httpResponse->getStatusCode() > 400) {
            throw new \Exception('yandex status code: ' . $httpResponse->getStatusCode());
        }
        $jsonResponse = json_decode($httpResponse->getBody(), false, 512, JSON_THROW_ON_ERROR);
        if (empty($jsonResponse->blocks)) {
            throw new \Exception('blocks is empty');
        }
        $block = current($jsonResponse->blocks);
        if (!isset($block->html)) {
            throw new \Exception('html not found');
        }
        return (new Dom())->load($block->html);
    }

    public function parse(Dom $dom): Data\Videos {
        $videos = new Data\Videos();
        foreach ($dom->root->find('div[data-video]') as $element) {
            try {
                $videoData = \GuzzleHttp\json_decode(htmlspecialchars_decode($element->getAttribute('data-video')), false, 512, JSON_THROW_ON_ERROR);
                if (!isset($videoData->url)) {
                    throw new \Exception('url not found');
                } else if (!isset($videoData->pos) || !is_numeric($videoData->pos)) {
                    throw new \Exception('pos not found or not numeric');
                } else if (!isset($videoData->greenHost)) {
                    throw new \Exception('greenHost not found');
                } else if (!isset($videoData->hosting)) {
                    throw new \Exception('hosting not found');
                } else if (!isset($videoData->playerId)) {
                    throw new \Exception('playerId not found');
                } else if (!isset($videoData->id) || !is_numeric($videoData->id)) {
                    throw new \Exception('id not found or not numeric');
                } else if (!isset($videoData->title)) {
                    throw new \Exception('title not found');
                } else if (!isset($videoData->player->noAutoplayHtml)) {
                    throw new \Exception('noAutoplayHtml not found');
                } else if (!isset($videoData->counters->toHostingLoaded->stredParams->duration) || !is_numeric($videoData->id)) {
                    throw new \Exception('duration not found or not numeric');
                }
                $iframeElement = (new Dom())->load($videoData->player->noAutoplayHtml)->root->find('iframe[src]');
                if ($iframeElement->getIterator()->count() == 0) {
                    throw new \Exception('iframe not found');
                }
                $iframe = $iframeElement->getIterator()->current()->getAttribute('src');
                $thumbs = $element->find('img[class=thumb-image__image]');
                if ($thumbs->count() == 0) {
                    throw new \Exception('thumb not found');
                }
                $thumb = $thumbs->getIterator()->current()->getAttribute('src');
                $video = new Data\Video(
                        $videoData->url,
                        $videoData->pos,
                        $videoData->greenHost,
                        $videoData->hosting,
                        $videoData->playerId,
                        (int) $videoData->id,
                        $videoData->title,
                        $iframe,
                        $thumb,
                        (int) $videoData->counters->toHostingLoaded->stredParams->duration
                );
                $videos->put($video);
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
        return $videos;
    }

    public function getErrors(): array {
        return $this->errors;
    }

}
