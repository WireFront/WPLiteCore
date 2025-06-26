<?php

namespace WPLite\Exceptions;

/**
 * Exception thrown when configuration is invalid or missing
 */
class ConfigException extends WPLiteException
{
    public function __construct(
        string $message = "Configuration error",
        string $configKey = '',
        array $context = [],
        \Exception $previous = null
    ) {
        $context['config_key'] = $configKey;
        parent::__construct($message, 500, $context, $previous);
    }
}
