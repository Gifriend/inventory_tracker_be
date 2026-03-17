<?php

namespace App\Traits;

trait ApiResponse
{
      protected function successResponse($data = null, $message = null, $code = 200)
      {
            return response()->json([
                  'status' => 'success',
                  'message' => $message,
                  'data' => $data
            ], $code);
      }

      protected function errorResponse($message = null, $code = 400, $data = null)
      {
            return response()->json([
                  'status' => 'error',
                  'message' => $message,
                  'data' => $data
            ], $code);
      }
}
