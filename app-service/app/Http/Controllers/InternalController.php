<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;

class InternalController extends Controller
{
    private AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function auditLog(Request $request)
    {
        $action = $request->input('action');

        switch ($action) {
            case 'LOGIN':
                $this->auditService->logLogin($request->all());
                break;

            case 'LOGOUT':
                $this->auditService->logLogout($request->all());
                break;

            case 'FAILED_LOGIN':
                $this->auditService->logFailedLogin($request->all());
                break;

            default:
                return response()->json([
                    'message' => 'Unknown action: ' . $action
                ], 400);
        }

        return response()->json([
            'message' => 'Audit log recorded successfully'
        ]);
    }
}
