<?php

namespace WPLite\Exceptions;

/**
 * Exception thrown when validation fails
 */
class ValidationException extends WPLiteException
{
    protected array $errors;

    public function __construct(
        string $message = "Validation failed",
        array $errors = [],
        array $context = [],
        \Exception $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($message, 422, $context, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->getErrors()
        ]);
    }
}
