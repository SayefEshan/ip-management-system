<?php

namespace App\Http\Controllers;

use App\Models\IPAddress;
use App\Services\AuditLogService;
use App\Http\Requests\StoreIPAddressRequest;
use App\Http\Requests\UpdateIPAddressRequest;
use App\Http\Resources\IPAddressResource;
use App\Http\Resources\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IPAddressController extends Controller
{
    private AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function index(Request $request)
    {
        $ipAddresses = IPAddress::orderBy('created_at', 'desc')
            ->paginate(20);

        return ApiResponse::success(
            IPAddressResource::collection($ipAddresses),
            'IP addresses retrieved successfully'
        );
    }

    public function store(StoreIPAddressRequest $request)
    {
        

        $validated = $request->validated();

        // Validate IP address format
        $ipVersion = $this->validateIPAddress($validated['ip_address']);
        if (!$ipVersion) {
            return ApiResponse::error('Invalid IP address format', null, 422);
        }

        // Get user context from header
        $userContext = $this->getUserContext($request);

        DB::beginTransaction();
        try {
            // Check if IP already exists
            $existing = IPAddress::where('ip_address', $validated['ip_address'])->first();
            if ($existing) {
                return ApiResponse::error('IP address already exists', null, 409);
            }

            // Create IP address
            $ipAddress = IPAddress::create([
                'ip_address' => $validated['ip_address'],
                'ip_version' => $ipVersion,
                'label' => $validated['label'],
                'comment' => $validated['comment'],
                'created_by' => $userContext['email']
            ]);

            // Log the action
            $this->auditService->logIpCreated($ipAddress, $userContext);

            DB::commit();

            return ApiResponse::success(
                new IPAddressResource($ipAddress),
                'IP address created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Failed to create IP address', null, 500);
        }
    }

    public function update(UpdateIPAddressRequest $request, $id)
    {
        

        $ipAddress = IPAddress::find($id);
        if (!$ipAddress) {
            return ApiResponse::error('IP address not found', null, 404);
        }

        // Get user context
        $userContext = $this->getUserContext($request);

        // Check permissions
        if (!$ipAddress->canBeModifiedBy($userContext['email'], $userContext['is_super_admin'])) {
            return ApiResponse::error('You do not have permission to modify this IP address', null, 403);
        }

        DB::beginTransaction();
        try {
            $validated = $request->validated();

        // Store old values for audit
            $oldValues = [
                'label' => $ipAddress->label,
                'comment' => $ipAddress->comment
            ];

            // Update IP address
            $ipAddress->update([
                'label' => $validated['label'],
                'comment' => $validated['comment']
            ]);

            // Log the action
            $this->auditService->logIpUpdated($ipAddress, $oldValues, $userContext);

            DB::commit();

            return ApiResponse::success(
                new IPAddressResource($ipAddress),
                'IP address updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Failed to update IP address', null, 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $ipAddress = IPAddress::find($id);
        if (!$ipAddress) {
            return ApiResponse::error('IP address not found', null, 404);
        }

        // Get user context
        $userContext = $this->getUserContext($request);

        // Only super admin can delete
        if (!$userContext['is_super_admin']) {
            return ApiResponse::error('Only super admin can delete IP addresses', null, 403);
        }

        DB::beginTransaction();
        try {
            // Log before deletion
            $this->auditService->logIpDeleted($ipAddress, $userContext);

            // Soft delete
            $ipAddress->delete();

            DB::commit();

            return ApiResponse::success(null, 'IP address deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Failed to delete IP address', null, 500);
        }
    }

    private function validateIPAddress($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'IPv4';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'IPv6';
        }

        return false;
    }

    private function getUserContext(Request $request)
    {
        $context = json_decode($request->header('X-User-Context'), true);

        return array_merge($context, [
            'ip_address' => $request->header('X-Forwarded-For') ?? $request->ip()
        ]);
    }
}
