<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    protected function handleTryCatch(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', $e->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Data not found', null, Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function successResponse($data = null, string $message = 'Success', int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    protected function errorResponse(string $message, $errors = null, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}
