<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use Psr\Log\LoggerInterface;

/**
 * Trait for consistent API error handling across controllers.
 *
 * Provides methods to:
 * - Return safe error responses without leaking internal details
 * - Log full error details for debugging
 * - Maintain consistent response format
 */
trait ApiErrorTrait {
    /**
     * Return a safe error response that doesn't leak internal details.
     *
     * Logs full exception details for debugging while returning
     * a generic user-friendly message to the client.
     *
     * @param \Exception $e The caught exception
     * @param string $publicMessage User-friendly error message
     * @param int $status HTTP status code
     * @param array $context Additional context for logging
     * @return DataResponse
     */
    protected function safeErrorResponse(
        \Exception $e,
        string $publicMessage,
        int $status = Http::STATUS_INTERNAL_SERVER_ERROR,
        array $context = []
    ): DataResponse {
        $errorId = uniqid('err_');

        // Get logger - trait expects $this->logger to be set
        $logger = $this->getLogger();

        // Log full details for debugging
        $logger->error('IntraVox API Error', array_merge([
            'errorId' => $errorId,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'class' => get_class($e),
        ], $context));

        // Return generic message to client with error ID for support reference
        return new DataResponse([
            'success' => false,
            'error' => $publicMessage,
            'errorId' => $errorId,
        ], $status);
    }

    /**
     * Return a client error response for validation/input errors.
     *
     * These are safe to show to users as they represent client mistakes,
     * not internal server errors.
     *
     * @param string $message Error message (safe to display)
     * @param int $status HTTP status code (default 400)
     * @return DataResponse
     */
    protected function clientErrorResponse(
        string $message,
        int $status = Http::STATUS_BAD_REQUEST
    ): DataResponse {
        return new DataResponse([
            'success' => false,
            'error' => $message,
        ], $status);
    }

    /**
     * Return a forbidden error response.
     *
     * @param string $message Error message
     * @return DataResponse
     */
    protected function forbiddenResponse(string $message = 'Access denied'): DataResponse {
        return new DataResponse([
            'success' => false,
            'error' => $message,
        ], Http::STATUS_FORBIDDEN);
    }

    /**
     * Return a not found error response.
     *
     * @param string $message Error message
     * @return DataResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): DataResponse {
        return new DataResponse([
            'success' => false,
            'error' => $message,
        ], Http::STATUS_NOT_FOUND);
    }

    /**
     * Get the logger instance.
     * Controllers using this trait should have a $logger property.
     *
     * @return LoggerInterface
     */
    abstract protected function getLogger(): LoggerInterface;
}
