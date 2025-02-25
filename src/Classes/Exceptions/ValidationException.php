<?php

namespace Classes\Exceptions;

use Exception;
use Throwable;

class ValidationException extends Exception {
    protected $errors = [];

    public function __construct(string|array $message = "", int $code = 0, ?Throwable $previous = null) {
        // Handle both string and array messages
        if (is_array($message)) {
            $this->errors = $message;
            $message = implode(', ', $message);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Check if there are errors for a specific field
     */
    public function hasError(string $field): bool {
        return isset($this->errors[$field]);
    }

    /**
     * Get error for a specific field
     */
    public function getError(string $field): ?string {
        return $this->errors[$field] ?? null;
    }

    /**
     * Add an error
     */
    public function addError(string $field, string $message): void {
        $this->errors[$field] = $message;
    }

    /**
     * Get all errors as a formatted string
     */
    public function getFormattedErrors(): string {
        if (empty($this->errors)) {
            return $this->getMessage();
        }

        return implode("\n", array_map(
            fn($field, $message) => "$field: $message",
            array_keys($this->errors),
            array_values($this->errors)
        ));
    }
}