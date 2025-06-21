<?php

namespace App\Http\Controllers;

use App\Models\IPAddress;
use App\Services\AuditLogService;
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

        return response()->json($ipAddresses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string',
            'label' => 'required|string|max:255',
            'comment' => 'nullable|string'
        ]);

        // Validate IP address format
        $ipVersion = $this->validateIPAddress($request->ip_address);
        if (!$ipVersion) {
            return response()->json([
                'message' => 'Invalid IP address format'
            ], 422);
        }

        // Get user context from header
        $userContext = $this->getUserContext($request);

        DB::beginTransaction();
        try {
            // Check if IP already exists
            $existing = IPAddress::where('ip_address', $request->ip_address)->first();
            if ($existing) {
                return response()->json([
                    'message' => 'IP address already exists'
                ], 409);
            }

            // Create IP address
            $ipAddress = IPAddress::create([
                'ip_address' => $request->ip_address,
                'ip_version' => $ipVersion,
                'label' => $request->label,
                'comment' => $request->comment,
                'created_by' => $userContext['id']
            ]);

            // Log the action
            $this->auditService->logIpCreated($ipAddress, $userContext);

            DB::commit();

            return response()->json([
                'message' => 'IP address created successfully',
                'data' => $ipAddress
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create IP address'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'comment' => 'nullable|string'
        ]);

        $ipAddress = IPAddress::find($id);
        if (!$ipAddress) {
            return response()->json([
                'message' => 'IP address not found'
            ], 404);
        }

        // Get user context
        $userContext = $this->getUserContext($request);

        // Check permissions
        if (!$ipAddress->canBeModifiedBy($userContext['id'], $userContext['is_super_admin'])) {
            return response()->json([
                'message' => 'You do not have permission to modify this IP address'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Store old values for audit
            $oldValues = [
                'label' => $ipAddress->label,
                'comment' => $ipAddress->comment
            ];

            // Update IP address
            $ipAddress->update([
                'label' => $request->label,
                'comment' => $request->comment
            ]);

            // Log the action
            $this->auditService->logIpUpdated($ipAddress, $oldValues, $userContext);

            DB::commit();

            return response()->json([
                'message' => 'IP address updated successfully',
                'data' => $ipAddress
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update IP address'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $ipAddress = IPAddress::find($id);
        if (!$ipAddress) {
            return response()->json([
                'message' => 'IP address not found'
            ], 404);
        }

        // Get user context
        $userContext = $this->getUserContext($request);

        // Only super admin can delete
        if (!$userContext['is_super_admin']) {
            return response()->json([
                'message' => 'Only super admin can delete IP addresses'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Log before deletion
            $this->auditService->logIpDeleted($ipAddress, $userContext);

            // Soft delete
            $ipAddress->delete();

            DB::commit();

            return response()->json([
                'message' => 'IP address deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete IP address'
            ], 500);
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
