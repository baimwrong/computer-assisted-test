<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Record an audit log entry.
     */
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?array $payload = null): AuditLog
    {
        return AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'payload'     => $payload,
            'ip_address'  => Request::ip(),
        ]);
    }
}
