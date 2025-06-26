<?php

namespace WPLite\Exceptions;

/**
 * Exception thrown when API requests fail
 */
class ApiException extends WPLiteException
{
    protected int $httpStatusCode;
    protected array $responseHeaders;

    public function __construct(
        string $message = "API request failed",
        int $code = 0,
        int $httpStatusCode = 500,
        array $responseHeaders = [],
        array $context = [],
        ?\Throwable $previous = null
    ) {
        $this->httpStatusCode = $httpStatusCode;
        $this->responseHeaders = $responseHeaders;
        
        parent::__construct($message, $code, $context, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'http_status' => $this->getHttpStatusCode(),
            'headers' => $this->getResponseHeaders()
        ]);
    }
}
