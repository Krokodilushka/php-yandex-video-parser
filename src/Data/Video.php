<?php

namespace YandexVideoParser\Data;

class Video {

    private $url;
    private $pos;
    private $greenHost;
    private $hosting;
    private $playerId;
    private $id;
    private $title;
    private $iframe;
    private $thumb;
    private $duration;

    public function __construct(string $url, int $pos, string $greenHost, string $hosting, string $playerId, int $id, string $title, string $iframe, string $thumb, int $duration) {
        $this->url = $url;
        $this->pos = $pos;
        $this->greenHost = $greenHost;
        $this->hosting = $hosting;
        $this->playerId = $playerId;
        $this->id = $id;
        $this->title = $title;
        $this->iframe = $iframe;
        $this->thumb = $thumb;
        $this->duration = $duration;
    }

    function getUrl(): string {
        return $this->url;
    }

    function getPos(): int {
        return $this->pos;
    }

    function getGreenHost(): string {
        return $this->greenHost;
    }

    function getHosting(): string {
        return $this->hosting;
    }

    function getPlayerId(): string {
        return $this->playerId;
    }

    function getId(): int {
        return $this->id;
    }

    function getTitle(): string {
        return $this->title;
    }

    function getIframe(): string {
        return $this->iframe;
    }

    function getThumb(): string {
        return $this->thumb;
    }

    function getDuration(): int {
        return $this->duration;
    }

}
