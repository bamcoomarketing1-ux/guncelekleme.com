<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => User::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'data' => $user], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->except(['password']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Kullanıcı güncellendi.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        User::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Kullanıcı silindi.']);
    }

    public function verifyAllEmails(): JsonResponse
    {
        User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);
        return response()->json(['status' => 'success', 'message' => 'Tüm e-postalar doğrulandı.']);
    }
}
