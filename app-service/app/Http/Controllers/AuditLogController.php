<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\IPAddress;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\ApiResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function sessionLogs(Request $request)
    {
        $userContext = $this->getUserContext($request);

        $logs = AuditLog::where('session_id', $userContext['session_id'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $this->attachIpAddressToLogs($logs);

        return ApiResponse::success(
            AuditLogResource::collection($logs),
            'Audit logs retrieved successfully'
        );
    }

    public function userLogs(Request $request)
    {
        $userContext = $this->getUserContext($request);

        $logs = AuditLog::where('user_id', $userContext['id'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $this->attachIpAddressToLogs($logs);

        return ApiResponse::success(
            AuditLogResource::collection($logs),
            'Audit logs retrieved successfully'
        );
    }

    public function ipSessionLogs(Request $request, $ip)
    {
        $userContext = $this->getUserContext($request);

        $ipAddress = IPAddress::withTrashed()->where('ip_address', $ip)->first();

        if (!$ipAddress) {
            return ApiResponse::error('IP address not found', null, 404);
        }

        $logs = AuditLog::where('session_id', $userContext['session_id'])
            ->where('entity_type', 'ip_address')
            ->where('entity_id', $ipAddress->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $this->attachIpAddressToLogs($logs);

        return ApiResponse::success(
            AuditLogResource::collection($logs),
            'Audit logs retrieved successfully'
        );
    }


    public function ipLogs(Request $request, $ip)
    {
        $ipAddress = IPAddress::withTrashed()->where('ip_address', $ip)->first();

        if (!$ipAddress) {
            return ApiResponse::error('IP address not found', null, 404);
        }

        $logs = AuditLog::where('entity_type', 'ip_address')
            ->where('entity_id', $ipAddress->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $this->attachIpAddressToLogs($logs);

        return ApiResponse::success(
            AuditLogResource::collection($logs),
            'Audit logs retrieved successfully'
        );
    }

    public function allLogs(Request $request)
    {
        $query = AuditLog::query();

        // Optional filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        $this->attachIpAddressToLogs($logs);

        return ApiResponse::success(
            AuditLogResource::collection($logs),
            'Audit logs retrieved successfully'
        );
    }

    private function getUserContext(Request $request)
    {
        return json_decode($request->header('X-User-Context'), true);
    }

    private function attachIpAddressToLogs($logs)
    {
        $ipIds = $logs->pluck('entity_id')->filter()->unique();
        $ipAddresses = IPAddress::withTrashed()->whereIn('id', $ipIds)->get()->keyBy('id');

        $logs->each(function ($log) use ($ipAddresses) {
            if ($log->entity_type === 'ip_address' && isset($ipAddresses[$log->entity_id])) {
                $log->entity_ip = $ipAddresses[$log->entity_id]->ip_address;
            }
        });
    }
}
