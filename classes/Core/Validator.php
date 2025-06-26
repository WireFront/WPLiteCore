<?php

namespace WPLite\Core;

use WPLite\Exceptions\ValidationException;

/**
 * Input validation and sanitization utility
 */
class Validator
{
    private array $errors = [];

    /**
     * Validate required field
     */
    public function required(mixed $value, string $field): self
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            $this->errors[$field][] = "The {$field} field is required";
        }
        return $this;
    }

    /**
     * Validate URL format
     */
    public function url(mixed $value, string $field): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "The {$field} must be a valid URL";
        }
        return $this;
    }

    /**
     * Validate string length
     */
    public function length(mixed $value, string $field, int $min = 0, int $max = PHP_INT_MAX): self
    {
        if (!empty($value)) {
            $length = strlen((string)$value);
            if ($length < $min) {
                $this->errors[$field][] = "The {$field} must be at least {$min} characters";
            }
            if ($length > $max) {
                $this->errors[$field][] = "The {$field} must not exceed {$max} characters";
            }
        }
        return $this;
    }

    /**
     * Validate integer
     */
    public function integer(mixed $value, string $field): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = "The {$field} must be an integer";
        }
        return $this;
    }

    /**
     * Validate array
     */
    public function array(mixed $value, string $field): self
    {
        if (!empty($value) && !is_array($value)) {
            $this->errors[$field][] = "The {$field} must be an array";
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Throw validation exception if validation fails
     */
    public function validate(): void
    {
        if ($this->fails()) {
            throw new ValidationException('Validation failed', $this->errors());
        }
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize URL input
     */
    public static function sanitizeUrl(string $url): string
    {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize integer input
     */
    public static function sanitizeInt(mixed $input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize array input
     */
    public static function sanitizeArray(array $input): array
    {
        return array_map(function($value) {
            if (is_string($value)) {
                return self::sanitizeString($value);
            }
            if (is_array($value)) {
                return self::sanitizeArray($value);
            }
            return $value;
        }, $input);
    }
}
