<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Resources\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $userContext = $this->getUserContext($request);

        if (!$userContext['is_super_admin']) {
            return ApiResponse::error('Access denied. Super admin privileges required.', null, 403);
        }

        return $next($request);
    }

    private function getUserContext(Request $request)
    {
        return json_decode($request->header('X-User-Context'), true);
    }
}