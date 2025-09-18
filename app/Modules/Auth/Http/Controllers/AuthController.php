<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Models\Country;
use App\Models\User;
use App\Modules\Auth\Http\Requests\RequestOtpRequest;
use App\Modules\Auth\Http\Requests\VerifyOtpRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\OtpService;
use App\Modules\Referrals\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
        private readonly ReferralService $referrals,
    ) {}

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
        return response()->json(['data' => new UserResource($request->user())]);
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

