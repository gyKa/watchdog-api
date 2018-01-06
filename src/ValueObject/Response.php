<?php

namespace ValueObject;

final class Response
{
    /** @var string */
    private $url;

    /** @var int */
    private $httpCode;

    /** @var float */
    private $totalTime;

    /**
     * @param string $url
     * @param int $httpCode
     * @param float $totalTime
     */
    public function __construct(string $url, int $httpCode, float $totalTime)
    {
        $this->url = $url;
        $this->httpCode = $httpCode;
        $this->totalTime = $totalTime;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @return float
     */
    public function getTotalTime(): float
    {
        return $this->totalTime;
    }
}
