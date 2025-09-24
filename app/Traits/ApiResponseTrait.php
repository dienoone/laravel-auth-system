<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
  /**
   * Success response
   */
  protected function successResponse(
    string $message = 'Success',
    mixed $data = null,
    int $code = 200
  ): JsonResponse {
    $response = [
      'success' => true,
      'message' => $message,
    ];

    if ($data !== null) {
      $response['data'] = $data;
    }

    return response()->json($response, $code);
  }

  /**
   * Error response
   */
  protected function errorResponse(
    string $message = 'Error',
    mixed $errors = null,
    int $code = 400
  ): JsonResponse {
    $response = [
      'success' => false,
      'message' => $message,
    ];

    if ($errors !== null) {
      $response['errors'] = $errors;
    }

    return response()->json($response, $code);
  }

  /**
   * Validation error response
   */
  protected function validationErrorResponse(
    string $message = 'Validation failed',
    array $errors = []
  ): JsonResponse {
    return $this->errorResponse($message, $errors, 422);
  }

  /**
   * Unauthorized response
   */
  protected function unauthorizedResponse(
    string $message = 'Unauthorized'
  ): JsonResponse {
    return $this->errorResponse($message, null, 401);
  }

  /**
   * Forbidden response
   */
  protected function forbiddenResponse(
    string $message = 'Forbidden'
  ): JsonResponse {
    return $this->errorResponse($message, null, 403);
  }

  /**
   * Not found response
   */
  protected function notFoundResponse(
    string $message = 'Resource not found'
  ): JsonResponse {
    return $this->errorResponse($message, null, 404);
  }
}
