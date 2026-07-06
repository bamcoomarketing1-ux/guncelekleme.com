<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\DeviceSessionService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, DeviceSessionService $deviceSessions): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'nullable|string',
        ]);
        $user = User::create([
            'name' => $data['name'] ?? $data['username'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $this->sendVerificationEmail($user);
        $token = $deviceSessions->createUserToken($user, $request)->plainTextToken;

        return response()->json(['status' => 'success', 'message' => 'Kayıt başarılı. Doğrulama e-postası gönderildi.', 'token' => $token, 'user' => $user], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Şifre sıfırlama bağlantısı gönderildi.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->merge([
            'current_password' => $request->input('current_password', $request->input('currentPassword')),
            'password' => $request->input('password', $request->input('newPassword')),
            'password_confirmation' => $request->input('password_confirmation', $request->input('newPassword_confirmation')),
        ]);

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mevcut şifre hatalı.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Şifre güncellendi.',
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'E-posta zaten doğrulanmış.']);
        }

        if ($request->filled('code')) {
            return $this->verifyEmailWithCode($request);
        }

        return $this->resendVerification($request);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'E-posta zaten doğrulanmış.']);
        }

        $code = (string) random_int(100000, 999999);
        $user->update([
            'verification_code' => $code,
            'verification_expires_at' => now()->addMinutes(30),
        ]);

        $this->sendVerificationCodeEmail($user, $code);

        return response()->json(['status' => 'success', 'message' => 'Doğrulama kodu e-posta adresinize gönderildi.']);
    }

    private function verifyEmailWithCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();

        if (! $user->verification_code || ! hash_equals($user->verification_code, trim($request->input('code')))) {
            return response()->json(['message' => 'Kod hatalı veya süresi dolmuş.'], 422);
        }

        if ($user->verification_expires_at && $user->verification_expires_at->isPast()) {
            return response()->json(['message' => 'Kod hatalı veya süresi dolmuş.'], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_expires_at' => null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'E-posta adresiniz doğrulandı.']);
    }

    public function verifyEmailSigned(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Geçersiz veya süresi dolmuş bağlantı.'], 403);
        }

        $user = User::findOrFail($id);
        if (sha1($user->email) !== $hash) {
            return response()->json(['message' => 'Geçersiz doğrulama.'], 403);
        }

        $user->update(['email_verified_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'E-posta doğrulandı.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Şifre sıfırlandı.']);
    }

    public function adminLogout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    public function usePromo(Request $request, BalanceService $balance, XpService $xp): JsonResponse
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $user = $request->user();
        $promo = Promocode::where('code', $code)->where('is_active', true)->first();
        if (! $promo) {
            return response()->json(['message' => 'Geçersiz promosyon kodu.'], 422);
        }
        if ($promo->expired_at && $promo->expired_at->isPast()) {
            return response()->json(['message' => 'Promosyon kodunun süresi dolmuş.'], 422);
        }
        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) {
            return response()->json(['message' => 'Promosyon kodu kullanım limitine ulaştı.'], 422);
        }
        if (PromocodeUsage::where('user_id', $user->id)->where('promocode_id', $promo->id)->exists()) {
            return response()->json(['message' => 'Bu kodu zaten kullandınız.'], 422);
        }
        PromocodeUsage::create(['user_id' => $user->id, 'promocode_id' => $promo->id]);
        $promo->increment('used_count');
        $balance->adjust($user, (float) $promo->reward_amount, 'promo', $code);
        $xp->add($user, 'promo');

        return response()->json(['status' => 'success', 'message' => 'Promosyon kodu uygulandı.', 'reward' => $promo->reward_amount]);
    }

    private function sendVerificationEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user));
        } catch (\Throwable) {
            // log driver or missing SMTP — dev continues
        }
    }

    private function sendVerificationCodeEmail(User $user, string $code): void
    {
        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user, $code));
        } catch (\Throwable) {
            // log driver or missing SMTP — dev continues
        }
    }
}
