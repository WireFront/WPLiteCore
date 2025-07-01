<?php

namespace WPLite\Exceptions;

use Exception;

/**
 * Base exception class for WPLiteCore framework
 */
class WPLiteException extends Exception
{
    protected array $context;

    public function __construct(string $message = "", int $code = 0, array $context = [], ?\Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get additional context information
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convert exception to array format for API responses
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];
    }
}
