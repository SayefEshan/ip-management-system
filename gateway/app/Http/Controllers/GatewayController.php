<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    private $authServiceUrl;
    private $appServiceUrl;

    public function __construct()
    {
        $this->authServiceUrl = env('AUTH_SERVICE_URL', 'http://localhost:8001');
        $this->appServiceUrl = env('APP_SERVICE_URL', 'http://localhost:8002');
    }

    public function proxyToAuth(Request $request)
    {
        return $this->proxyRequest($request, $this->authServiceUrl);
    }

    private function proxyRequest(Request $request, $serviceUrl)
    {
        try {
            $path = $request->path();
            $method = $request->method();
            $headers = $this->getForwardHeaders($request);
           
            // Build the full URL
            $url = $serviceUrl . '/' . $path;

            // Add query parameters
            if ($request->getQueryString()) {
                $url .= '?' . $request->getQueryString();
            }

            // Make the request
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->send($method, $url, [
                    'json' => $request->all(),
                ]);

            // Return the response
            return response($response->body(), $response->status())
                ->withHeaders($response->headers());
        } catch (Exception $e) {
            Log::error('Gateway proxy error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Service unavailable',
                'details' => $e->getMessage(),
                'message' => 'Unable to process request'
            ], 503);
        }
    }

    private function getForwardHeaders(Request $request)
    {
        $headers = [
            'X-Forwarded-For' => $request->ip(),
            'X-Forwarded-Host' => $request->getHost(),
            'X-Forwarded-Proto' => $request->getScheme(),
            'X-Original-URI' => $request->getRequestUri(),
        ];

        // Forward authorization header
        if ($request->hasHeader('Authorization')) {
            $headers['Authorization'] = $request->header('Authorization');
        }

        // Forward session ID if present
        if ($request->hasHeader('X-Session-ID')) {
            $headers['X-Session-ID'] = $request->header('X-Session-ID');
        }

        // Forward user context if present
        if ($request->hasHeader('X-User-Context')) {
            $headers['X-User-Context'] = $request->header('X-User-Context');
        }

        return $headers;
    }
}
