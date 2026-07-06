<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManageController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Admin::orderBy('id')->get(['id', 'username', 'email', 'role', 'created_at']);
        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6',
            'role' => 'nullable|string',
        ]);
        $admin = Admin::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'Sistem Yöneticisi',
        ]);
        return response()->json(['status' => 'success', 'data' => $admin->only(['id', 'username', 'email', 'role', 'created_at'])], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Admin::findOrFail($id);
        $data = $request->only(['username', 'email', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $admin->update($data);
        return response()->json(['status' => 'success', 'data' => $admin->fresh()->only(['id', 'username', 'email', 'role', 'created_at'])]);
    }

    public function destroy(int $id): JsonResponse
    {
        Admin::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Admin silindi.']);
    }
}
