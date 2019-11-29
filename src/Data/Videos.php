<?php

namespace YandexVideoParser\Data;

class Videos implements \IteratorAggregate {

    private $videos = [];

    public function put(Video $video) {
        $this->videos[] = $video;
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->videos);
    }

}
