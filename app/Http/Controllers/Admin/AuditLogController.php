<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('search')) {
            $query->where('action', 'ilike', "%{$request->search}%");
        }

        $logs = $query->latest()->paginate(25);
        $actions = AuditLog::distinct()->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'actions'));
    }
}
