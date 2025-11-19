<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Models\Country;
use App\Models\User;
use App\Modules\Auth\Http\Requests\ChangePasswordRequest;
use App\Modules\Auth\Http\Requests\DeleteAccountRequest;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RequestOtpRequest;
use App\Modules\Auth\Http\Requests\UpdateBankAccountRequest;
use App\Modules\Auth\Http\Requests\VerifyOtpRequest;
use App\Notifications\UserAccountDeletedNotification;
use Illuminate\Support\Facades\Notification;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\OtpService;
use App\Modules\Referrals\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
        private readonly ReferralService $referrals,
    ) {}

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ], 401);
        }

        // Check if user is banned
        if ($user->isBanned()) {
            return response()->json([
                'message' => 'Account is banned',
                'errors' => [
                    'email' => ['Your account has been banned.'],
                ],
            ], 403);
        }

        $tokens = $this->auth->issueTokens($user);
        return response()->json([
            'data' => array_merge([
                'user' => (new UserResource($user))->resolve(),
            ], $tokens),
        ]);
    }

    public function requestOtp(RequestOtpRequest $request)
    {
        $this->otp->request($request->string('phone_with_cc'));
        return response()->json(['data' => ['sent' => true]]);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $phone = $request->string('phone_with_cc');
        $otp = $request->string('otp');
        abort_unless($this->otp->verify($phone, $otp), 422, 'Invalid OTP');

        $user = User::firstOrCreate(
            ['phone_with_cc' => $phone],
            [
                'currency' => $this->deriveCurrencyFromPhone($phone),
            ]
        );

        if ($code = $request->string('referral_code')->toString()) {
            $this->referrals->processSignupReferral($user, $code);
        }

        $tokens = $this->auth->issueTokens($user);
        return response()->json([
            'data' => array_merge([
                'user' => (new UserResource($user))->resolve(),
            ], $tokens),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json(['data' => new UserResource($user)]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['data' => ['logout' => true]]);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => ['required','string'],
        ]);
        $user = User::where('phone_with_cc', $request->string('phone_with_cc'))->first() ?? $request->user();
        abort_if(!$user, 401, 'Unauthenticated');
        $tokens = $this->auth->refresh($user, $request->string('refresh_token'));
        return response()->json(['data' => $tokens]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور الحالية غير صحيحة',
                'errors' => [
                    'current_password' => ['كلمة المرور الحالية غير صحيحة'],
                ],
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح',
            'data' => [
                'success' => true,
            ],
        ]);
    }

    public function updateBankAccount(UpdateBankAccountRequest $request)
    {
        $user = $request->user();

        $user->update([
            'bank_account' => $request->input('bank_account'),
            'iban' => $request->input('iban'),
            'bank_name' => $request->input('bank_name'),
            'bank_country_id' => $request->input('bank_country_id'),
        ]);

        return response()->json([
            'message' => 'تم تحديث معلومات الحساب البنكي بنجاح',
            'data' => [
                'bank_account' => $user->bank_account,
                'iban' => $user->iban,
                'bank_name' => $user->bank_name,
                'bank_country_id' => $user->bank_country_id,
            ],
        ]);
    }

    public function getBankAccount(Request $request)
    {
        $user = $request->user()->load('bankCountry');

        return response()->json([
            'data' => [
                'bank_account' => $user->bank_account,
                'iban' => $user->iban,
                'bank_name' => $user->bank_name,
                'bank_country_id' => $user->bank_country_id,
                'bank_country' => $user->bankCountry ? [
                    'id' => $user->bankCountry->id,
                    'name' => $user->bankCountry->name,
                ] : null,
            ],
        ]);
    }

    public function deleteAccount(DeleteAccountRequest $request)
    {
        $user = $request->user();

        // Verify password
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور غير صحيحة',
                'errors' => [
                    'password' => ['كلمة المرور غير صحيحة'],
                ],
            ], 422);
        }

        // Notify admins before deletion
        $admins = User::role('admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new UserAccountDeletedNotification($user));
        }

        // Delete all user's access tokens
        $user->tokens()->delete();

        // Soft delete the user account
        $user->delete();

        return response()->json([
            'message' => 'تم حذف حسابك بنجاح',
            'data' => [
                'success' => true,
            ],
        ]);
    }

    private function deriveCurrencyFromPhone(string $phone): string
    {
        // crude derivation based on CC; real impl should parse using libphonenumber
        $map = [
            '+20' => 'EGP', '+966' => 'SAR', '+971' => 'AED', '+1' => 'USD', '+44' => 'GBP', '+49' => 'EUR'
        ];
        foreach ($map as $cc => $cur) {
            if (str_starts_with($phone, $cc)) return $cur;
        }
        // fallback by user's selected country
        $country = Country::where('id', auth()->user()?->country_id)->first();
        return $country?->currency ?? 'USD';
    }
}

