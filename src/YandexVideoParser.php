<?php

namespace YandexVideoParser;

use GuzzleHttp\Client as HttpClient;
use PHPHtmlParser\Dom;
use YandexVideoParser\Exception\BlocksIsEmpty;
use YandexVideoParser\Exception\DurationNotFound;
use YandexVideoParser\Exception\GreenHostNotFound;
use YandexVideoParser\Exception\HostingNotFound;
use YandexVideoParser\Exception\HtmlNotFound;
use YandexVideoParser\Exception\PlayerIdNotFound;
use YandexVideoParser\Exception\PositionNotFound;
use YandexVideoParser\Exception\ThumbnailNotFound;
use YandexVideoParser\Exception\TitleNotFound;
use YandexVideoParser\Exception\UrlNotFound;
use YandexVideoParser\Exception\VideoIdNotFound;

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
            throw new BlocksIsEmpty;
        }
        $block = current($jsonResponse->blocks);
        if (!isset($block->html)) {
            throw new HtmlNotFound();
        }
        return (new Dom())->loadStr($block->html);
    }

    public function parse(Dom $dom): array
    {
        $res = [];
        foreach ($dom->root->find('div[data-video]') as $element) {
            $videoData = json_decode(htmlspecialchars_decode($element->getAttribute('data-video')), false, 512, JSON_THROW_ON_ERROR);
            if (!isset($videoData->url)) {
                throw new UrlNotFound();
            } else if (!isset($videoData->pos) || !is_numeric($videoData->pos)) {
                throw new PositionNotFound();
            } else if (!isset($videoData->greenHost)) {
                throw new GreenHostNotFound();
            } else if (!isset($videoData->hosting)) {
                throw new HostingNotFound();
            } else if (!isset($videoData->playerId)) {
                throw new PlayerIdNotFound();
            } else if (!isset($videoData->id) || !is_numeric($videoData->id)) {
                throw new VideoIdNotFound();
            } else if (!isset($videoData->title)) {
                throw new TitleNotFound();
            } else if (!isset($videoData->player->noAutoplayHtml)) {
                continue;
            } else if (!isset($videoData->counters->toHostingLoaded->stredParams->duration) || !is_numeric($videoData->id)) {
                throw new DurationNotFound();
            }
            $iframeElement = (new Dom())->loadStr($videoData->player->noAutoplayHtml)->root->find('iframe[src]');
            if ($iframeElement->getIterator()->count() == 0) {
                continue;
            }
            $iframe = $iframeElement->getIterator()->current()->getAttribute('src');
            $thumbs = $element->find('img[class=thumb-image__image]');
            if ($thumbs->count() == 0) {
                $thumb2 = $element->find('div[class=serp-item__preview serp-item__preview_rounded]');
                if ($thumb2->count() != 0 && preg_match('/background-image: url\((.*)\)/', $thumb2->getIterator()->current()->getAttribute('style'), $matches)) {
                    $thumb = $matches[1];
                } else {
                    throw new ThumbnailNotFound();
                }
            } else {
                $thumb = $thumbs->getIterator()->current()->getAttribute('src');
            }
            $res[] = [
                'id' => $videoData->id,
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
