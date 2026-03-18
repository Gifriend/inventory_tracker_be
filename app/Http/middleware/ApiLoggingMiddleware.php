<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $startedAt = microtime(true);

        $requestContext = [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            // 'full_url' => $request->fullUrl(),
            'route_name' => optional($request->route())->getName(),
            // 'ip' => $request->ip(),
            'user_id' => optional($request->user())->id,
            'query' => $request->query(),
            'payload' => $this->maskSensitiveInputs(
                $request->except(['password', 'password_confirmation', 'token', 'authorization'])
            ),
            // 'files' => $this->describeFiles($request),
        ];

        Log::info('API request', $requestContext);
        Log::channel('stderr')->info('API request', $requestContext);

        $response = $next($request);

        $responseContext = [
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'response_body' => $this->summarizeResponse($response),
        ];

        Log::info('API response', array_merge($requestContext, $responseContext));
        Log::channel('stderr')->info('API response', array_merge($requestContext, $responseContext));

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->is('favicon.ico') || str_starts_with($request->path(), '_debugbar');
    }

    private function describeFiles(Request $request): array
    {
        return $this->mapUploadedFiles($request->allFiles());
    }

    private function mapUploadedFiles(array $files): array
    {
        $mapped = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $mapped[$key] = $this->mapUploadedFiles($file);
                continue;
            }

            if ($file instanceof UploadedFile) {
                $mapped[$key] = [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size_kb' => round($file->getSize() / 1024, 1),
                ];
            }
        }

        return $mapped;
    }

    private function summarizeResponse(Response $response): array
    {
        $contentType = (string) $response->headers->get('Content-Type', '');
        $content = $response->getContent();

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'content_type' => 'json',
                    'data' => $this->truncateJson($decoded),
                ];
            }
        }

        return [
            'content_type' => $contentType ?: 'unknown',
            'length' => strlen($content),
            'preview' => substr($content, 0, 500),
        ];
    }

    private function truncateJson(mixed $data, int $depth = 0): mixed
    {
        if ($depth >= 3) {
            return '[truncated]';
        }

        if (is_array($data)) {
            $result = [];

            foreach ($data as $key => $value) {
                $result[$key] = $this->truncateJson($value, $depth + 1);
            }

            return $result;
        }

        return $data;
    }

    private function maskSensitiveInputs(array $input): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'authorization', 'auth_token'];

        Arr::forget($input, ['file', 'pdf_file']);
        $input = $this->maskRecursive($input, $sensitiveKeys);

        return $input;
    }

    private function maskRecursive(array $input, array $sensitiveKeys): array
    {
        foreach ($input as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $input[$key] = '[hidden]';
                continue;
            }

            if (is_array($value)) {
                $input[$key] = $this->maskRecursive($value, $sensitiveKeys);
            }
        }

        return $input;
    }
}
