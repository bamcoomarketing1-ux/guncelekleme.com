<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\SiteSetting;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Services\DeviceSessionService;
use App\Services\UploadService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    public function settings(): JsonResponse
    {
        $data = SiteSetting::current();
        $botUsername = TelegramSetting::current()->bot_username;
        if ($botUsername) {
            $data['telegram_bot_username'] = ltrim($botUsername, '@');
        }

        return response()->json(['data' => $data]);
    }

    public function updateSettings(Request $request, UploadService $upload): JsonResponse
    {
        $data = SiteSetting::current();
        $data = array_merge($data, $request->except([
            'site_logo', 'site_favicon', 'opening_gif', 'opening_gif_mobile',
            'logo', 'favicon', 'background_image', 'chat_bot_avatar', 'powered_by_logo',
            'remove_site_logo', 'remove_site_favicon', 'remove_opening_gif',
            'remove_opening_gif_mobile', 'remove_background_image', 'remove_powered_by_logo',
        ]));

        foreach ([
            'remove_site_logo' => 'site_logo',
            'remove_site_favicon' => 'site_favicon',
            'remove_opening_gif' => 'opening_gif',
            'remove_opening_gif_mobile' => 'opening_gif_mobile',
            'remove_background_image' => 'background_image',
            'remove_powered_by_logo' => 'powered_by_logo',
        ] as $flag => $key) {
            if ($request->boolean($flag)) {
                $data[$key] = null;
            }
        }

        foreach ([
            'site_logo' => ['key' => 'site_logo', 'folder' => 'logo'],
            'site_favicon' => ['key' => 'site_favicon', 'folder' => 'favicon'],
            'opening_gif' => ['key' => 'opening_gif', 'folder' => 'gifs'],
            'opening_gif_mobile' => ['key' => 'opening_gif_mobile', 'folder' => 'gifs'],
            'logo' => ['key' => 'site_logo', 'folder' => 'logo'],
            'favicon' => ['key' => 'site_favicon', 'folder' => 'favicon'],
            'background_image' => ['key' => 'background_image', 'folder' => 'site'],
            'chat_bot_avatar' => ['key' => 'chat_bot_avatar', 'folder' => 'site'],
            'powered_by_logo' => ['key' => 'powered_by_logo', 'folder' => 'footer'],
        ] as $field => $config) {
            if ($request->hasFile($field)) {
                $data[$config['key']] = $upload->storeImage($request->file($field), $config['folder']);
            }
        }

        $data = SiteSetting::normalizeData($data);
        SiteSetting::query()->updateOrCreate(['id' => 1], ['data' => $data]);

        return response()->json(['data' => $data]);
    }

    public function adminLogin(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $admin = Admin::where('email', $request->email)->first();
        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Giriş bilgileri hatalı.'], 422);
        }

        $token = $admin->createToken('admin')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Giriş başarılı!',
            'data' => [
                'token' => $token,
                'user' => $admin->only(['id', 'username', 'email', 'role']),
            ],
        ]);
    }

    public function adminMe(Request $request): JsonResponse
    {
        $admin = $request->user();
        return response()->json(['data' => $admin->only(['id', 'username', 'email', 'role'])]);
    }

    public function adminUsers(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 100);
        $search = trim((string) $request->get('search', ''));

        $q = User::query()->orderByDesc('id');
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $page = $q->paginate($perPage);

        return response()->json([
            'data' => $page->items(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ]);
    }

    public function userLogin(Request $request, XpService $xp, DeviceSessionService $deviceSessions): JsonResponse
    {
        $login = $request->input('login');
        $user = User::where('username', $login)->orWhere('email', $login)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Kimlik bilgileri hatalı.'], 401);
        }

        $user->update(['last_login_at' => now()]);
        $token = $deviceSessions->createUserToken($user, $request)->plainTextToken;

        return response()->json(['token' => $token, 'user' => $xp->userApiPayload($user)]);
    }

    public function currentUser(Request $request, XpService $xp): JsonResponse
    {
        return response()->json($xp->userApiPayload($request->user()));
    }
}
