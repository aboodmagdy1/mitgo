<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    /**
     * Success response with data
     */
    protected function successResponse($data = null, string $message = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json(array_filter($response, fn($value) => !is_null($value)), $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST, $errors = null): JsonResponse
    {
        $response = [
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json(array_filter($response, fn($value) => !is_null($value)), $statusCode);
    }

    /**
     * Resource response (for single resources)
     */
    protected function resourceResponse(JsonResource $resource, string $message = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return $this->successResponse($resource, $message, $statusCode);
    }

    /**
     * Collection response (for resource collections)
     */
    protected function collectionResponse(ResourceCollection $collection, string $message = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return $this->successResponse($collection, $message, $statusCode);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = null): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => $message,
             'data' => (object)[
                'data' => $paginator->items(),
                'pagination' => (object)[
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
             ]
        ];

        return response()->json(array_filter($response, fn($value) => !is_null($value)));
    }

    /**
     * Paginated resource response - transforms items through resource and preserves pagination
     */
    protected function paginatedResourceResponse(LengthAwarePaginator $paginator, string $resourceClass, string $message = null): JsonResponse
    {
        // Transform items through resource
        $transformedItems = $paginator->getCollection()->map(function ($item) use ($resourceClass) {
            return (new $resourceClass($item))->toArray(request());
        });
        
        // Set transformed items back to paginator
        $paginator->setCollection($transformedItems);
        
        return $this->paginatedResponse($paginator, $message);
    }

    // ========================================
    // SUCCESS RESPONSES (2xx)
    // ========================================

    /**
     * 200 OK - Standard success response
     */
    protected function ok($data = null, string $message = 'Request successful'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_OK);
    }

    /**
     * 201 Created - Resource created successfully
     */
    protected function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * 202 Accepted - Request accepted for processing
     */
    protected function accepted($data = null, string $message = 'Request accepted for processing'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_ACCEPTED);
    }

    /**
     * 204 No Content - Success with no content to return
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    // ========================================
    // CLIENT ERROR RESPONSES (4xx)
    // ========================================

    /**
     * 400 Bad Request - Invalid request syntax
     */
    protected function badRequest(string $message = 'Bad request', $errors = null): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_BAD_REQUEST, $errors);
    }

    /**
     * 401 Unauthorized - Authentication required
     */
    protected function unauthorized(string $message = 'Unauthorized access'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 403 Forbidden - Access denied
     */
    protected function forbidden(string $message = 'Access forbidden'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * 404 Not Found - Resource not found
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * 405 Method Not Allowed - HTTP method not allowed
     */
    protected function methodNotAllowed(string $message = 'Method not allowed'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * 409 Conflict - Resource conflict
     */
    protected function conflict(string $message = 'Resource conflict'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_CONFLICT);
    }

    /**
     * 422 Unprocessable Entity - Validation errors
     */
    protected function validationError(string $message = 'Validation failed', $errors = null): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * 429 Too Many Requests - Rate limit exceeded
     */
    protected function tooManyRequests(string $message = 'Too many requests'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_TOO_MANY_REQUESTS);
    }

    // ========================================
    // SERVER ERROR RESPONSES (5xx)
    // ========================================

    /**
     * 500 Internal Server Error - Generic server error
     */
    protected function internalServerError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 502 Bad Gateway - Invalid response from upstream server
     */
    protected function badGateway(string $message = 'Bad gateway'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_BAD_GATEWAY);
    }

    /**
     * 503 Service Unavailable - Service temporarily unavailable
     */
    protected function serviceUnavailable(string $message = 'Service unavailable'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    // ========================================
    // CONVENIENCE METHODS
    // ========================================

    /**
     * Handle Laravel validation errors
     */
    protected function handleValidationErrors($validator): JsonResponse
    {
        return $this->validationError('Validation failed', $validator->errors());
    }

    /**
     * Handle exceptions
     */
    protected function handleException(\Exception $e, string $message = null): JsonResponse
    {
        $message = $message ?? $e->getMessage();
        
        if (config('app.debug')) {
            return $this->errorResponse($message, Response::HTTP_INTERNAL_SERVER_ERROR, [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $this->internalServerError($message);
    }

    /**
     * Custom status response
     */
    protected function customResponse($data, string $message, int $statusCode, bool $success = null): JsonResponse
    {
        $success = $success ?? ($statusCode >= 200 && $statusCode < 300);
        
        $response = [
            'status' => $success,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json(array_filter($response, fn($value) => !is_null($value)), $statusCode);
    }

    /**
     * Response with meta information
     */
    protected function responseWithMeta($data, array $meta = [], string $message = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ];

        return response()->json(array_filter($response, fn($value) => !is_null($value)), $statusCode);
    }

    /**
     * Empty data response (for empty collections)
     */
    protected function emptyDataResponse(string $message = 'No data found'): JsonResponse
    {
        return $this->successResponse([], $message);
    }

    /**
     * Boolean result response
     */
    protected function booleanResponse(bool $result, string $successMessage = 'Operation successful', string $failureMessage = 'Operation failed'): JsonResponse
    {
        return $result 
            ? $this->ok(['status' => true], $successMessage)
            : $this->badRequest($failureMessage, ['status' => false]);
    }
} 