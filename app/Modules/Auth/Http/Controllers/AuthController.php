<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Enums\UserType;
use App\Models\Country;
use App\Models\User;
use App\Modules\Auth\Http\Requests\ChangePasswordRequest;
use App\Modules\Auth\Http\Requests\DeleteAccountRequest;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Requests\RequestOtpRequest;
use App\Modules\Auth\Http\Requests\UpdateBankAccountRequest;
use App\Modules\Auth\Http\Requests\UpdateProfileRequest;
use App\Modules\Auth\Http\Requests\VerifyOtpRequest;
use App\Notifications\TrainerRegistrationPendingApprovalNotification;
use App\Notifications\UserAccountDeletedNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\Upload;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\OtpService;
use App\Modules\Referrals\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
        private readonly ReferralService $referrals,
    ) {}

    public function register(RegisterRequest $request)
    {
        $type = $request->input('type');
        $phone = $request->input('phone_with_cc');
        $userType = $type === 'captain' ? UserType::CAPTAIN : UserType::USER;

        $user = User::create([
            'name' => $request->input('name'),
            'password' => Hash::make($request->input('password')),
            'phone_with_cc' => $phone,
            'user_type' => $userType,
            'currency' => $this->deriveCurrencyFromPhone($phone),
        ]);

        $role = $type === 'captain' ? 'TRAINER' : 'USER';
        $user->assignRole($role);
        if ($type === 'captain') {
            $user->trainerProfile()->create([
                'pending_approval' => true,
                'pending_approval_at' => now(),
            ]);
            $user->update([
                'banned_until' => now()->addYears(10),
                'banned_reason' => 'مطلوب تنشيط من الإدارة',
            ]);
        }

        if ($code = $request->input('referral_code')) {
            $this->referrals->processSignupReferral($user, $code);
        }

        if ($type === 'captain') {
            $admins = User::role('ADMIN')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new TrainerRegistrationPendingApprovalNotification($user));
            }

            return response()->json([
                'message' => 'تم إنشاء الحساب وبانتظار موافقة الإدارة على التنشيط',
                'data' => [
                    'user' => (new UserResource($user))->resolve(),
                    'requires_admin_approval' => true,
                    'approval_status' => 'pending',
                ],
            ], 201);
        }

        $tokens = $this->auth->issueTokens($user);
        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح',
            'data' => array_merge([
                'user' => (new UserResource($user))->resolve(),
            ], $tokens),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('phone_with_cc', $request->input('phone_with_cc'))->first();

        if (!$user || !$user->password || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'phone_with_cc' => ['The provided credentials are incorrect.'],
                ],
            ], 401);
        }

        $user->loadMissing('trainerProfile');

        if ($user->hasRole('TRAINER') && $user->trainerProfile?->pending_approval) {
            return response()->json([
                'message' => 'Account pending approval',
                'errors' => [
                    'phone_with_cc' => ['الحساب بانتظار موافقة الإدارة على التنشيط.'],
                ],
            ], 403);
        }

        // Check if user is banned
        if ($user->isBanned()) {
            return response()->json([
                'message' => 'Account is banned',
                'errors' => [
                    'phone_with_cc' => ['Your account has been banned.'],
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
        $userResource = new UserResource($user);
        $data = $userResource->resolve();
        $data['user_type'] = $user->hasRole('TRAINER') || $user->user_type === UserType::CAPTAIN ? 'trainer' : 'user';

        return response()->json(['data' => $data]);
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
        $user = User::where('phone_with_cc', $request->string('mobile')->toString())->first();

        if (!$user) {
            return response()->json([
                'message' => 'رقم الجوال غير مسجل',
                'errors' => [
                    'mobile' => ['رقم الجوال غير مسجل'],
                ],
            ], 422);
        }

        // Reset password by mobile number (public endpoint).
        $user->password = Hash::make($request->input('new_password'));
        $user->save();
        $user->tokens()->delete();

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

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Update name if provided
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        // Update email if provided
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        // Update phone if provided
        if (isset($data['phone_with_cc'])) {
            $user->phone_with_cc = $data['phone_with_cc'];
        }

        // Update password if provided
        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // Handle profile picture upload
        $profileFile = $request->file('profile_picture') ?: $request->file('image');
        if ($profileFile) {
            // Delete old profile picture if exists
            if ($user->profile_picture_id) {
                $oldUpload = Upload::find($user->profile_picture_id);
                if ($oldUpload) {
                    Storage::disk($oldUpload->disk)->delete($oldUpload->path);
                    $oldUpload->delete();
                }
            }

            // Store new profile picture
            $disk = config('filesystems.default', 'public');
            $path = $profileFile->store('profiles', $disk);

            $upload = Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $profileFile->getMimeType(),
                'size' => $profileFile->getSize(),
            ]);

            $user->profile_picture_id = $upload->id;
        }

        $user->save();

        // Reload user with profile picture relationship
        $user->load('profilePicture');

        return response()->json([
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => new UserResource($user),
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

    private function deriveCurrencyFromPhone(?string $phone): string
    {
        if (!$phone) {
            return 'USD';
        }

        // crude derivation based on CC; real impl should parse using libphonenumber
        $map = [
            '+20' => 'EGP', '+966' => 'SAR', '+971' => 'AED', '+1' => 'USD', '+44' => 'GBP', '+49' => 'EUR'
        ];
        foreach ($map as $cc => $cur) {
            if (str_starts_with($phone, $cc)) return $cur;
        }

        return 'USD';
    }
}
