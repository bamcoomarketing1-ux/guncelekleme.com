<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission = '*'): Response
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $role = $admin->role ?? 'Sistem Yöneticisi';
        $permissions = $admin->permissions ?? config("platform.admin_permissions.{$role}", ['*']);

        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return $next($request);
        }

        $resource = explode('/', trim($request->path(), '/'))[1] ?? '';
        if (in_array($resource, $permissions, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Bu işlem için yetkiniz yok.'], 403);
    }
}
