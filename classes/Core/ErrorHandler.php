<?php

namespace WPLite\Core;

use WPLite\Exceptions\WPLiteException;

/**
 * Error handler for WPLiteCore framework
 * Provides consistent error handling and response formatting
 */
class ErrorHandler
{
    private bool $debugMode;
    private string $logPath;

    public function __construct(bool $debugMode = false, string $logPath = '')
    {
        $this->debugMode = $debugMode;
        $this->logPath = $logPath ?: getcwd() . '/logs/error.log';
    }

    /**
     * Register error handlers
     */
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $this->logError([
            'type' => 'PHP Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line
        ]);

        if ($this->debugMode) {
            $this->displayError([
                'type' => 'PHP Error',
                'message' => $message,
                'file' => $file,
                'line' => $line
            ]);
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException(\Throwable $exception): void
    {
        $error = [
            'type' => 'Uncaught Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        if ($exception instanceof WPLiteException) {
            $error['context'] = $exception->getContext();
        }

        $this->logError($error);

        if ($this->debugMode) {
            $this->displayError($error);
        } else {
            $this->displayGenericError();
        }
    }

    /**
     * Handle fatal errors on shutdown
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->logError([
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            if ($this->debugMode) {
                $this->displayError($error);
            } else {
                $this->displayGenericError();
            }
        }
    }

    /**
     * Format exception for API response
     */
    public function formatForApi(\Throwable $exception): array
    {
        $response = [
            'error' => true,
            'message' => $exception->getMessage()
        ];

        if ($exception instanceof WPLiteException) {
            $response = array_merge($response, $exception->toArray());
        }

        if ($this->debugMode) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        return $response;
    }

    /**
     * Log error to file
     */
    private function logError(array $error): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $error
        ];

        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(
            $this->logPath,
            json_encode($logEntry, JSON_PRETTY_PRINT) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Display detailed error (debug mode)
     */
    private function displayError(array $error): void
    {
        http_response_code(500);
        header('Content-Type: application/json');
        
        echo json_encode([
            'error' => true,
            'message' => 'An error occurred',
            'details' => $error
        ], JSON_PRETTY_PRINT);
        
        exit;
    }

    /**
     * Display generic error (production mode)
     */
    private function displayGenericError(): void
    {
        http_response_code(500);
        header('Content-Type: application/json');
        
        echo json_encode([
            'error' => true,
            'message' => 'An internal server error occurred'
        ]);
        
        exit;
    }
}
