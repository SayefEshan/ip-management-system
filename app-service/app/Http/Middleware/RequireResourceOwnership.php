<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Resources\ApiResponse;
use App\Models\IPAddress;
use Symfony\Component\HttpFoundation\Response;

class RequireResourceOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $userContext = $this->getUserContext($request);

        // Super admin can access any resource
        if ($userContext['is_super_admin']) {
            return $next($request);
        }

        // For IP address resources, check ownership
        if ($request->route('id')) {
            $ipAddress = IPAddress::find($request->route('id'));
            
            if (!$ipAddress) {
                return ApiResponse::error('Resource not found', null, 404);
            }

            if (!$ipAddress->canBeModifiedBy($userContext['email'], $userContext['is_super_admin'])) {
                return ApiResponse::error('Access denied. You can only modify resources you created.', null, 403);
            }
        }

        return $next($request);
    }

    private function getUserContext(Request $request)
    {
        return json_decode($request->header('X-User-Context'), true);
    }
}