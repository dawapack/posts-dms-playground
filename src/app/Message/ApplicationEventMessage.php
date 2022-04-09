<?php

declare(strict_types=1);

namespace PostsDMS\Message;

use Chassis\Framework\Adapters\Message\ApplicationMessageInterface;

class ApplicationEventMessage implements ApplicationMessageInterface
{
    private array $headers;
    private array $payload;

    /**
     * @param array $body
     * @param array $headers
     */
    public function __construct(array $body, array $headers = [])
    {
        $this->payload = $body;
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    public function setHeader(string $name, $value): ApplicationEventMessage
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
