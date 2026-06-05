<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AdminStoreUserRequest;
use App\Http\Requests\Admin\AdminUpdateUserRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Models\TrainerProfile;
use App\Models\Upload;
use App\Models\User;
use App\Models\UserRequest;
use App\Notifications\TrainerProfileApprovalNotification;
use App\Services\Admin\UserPurgeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UsersController extends BaseController
{
    private const TRAINER_PROFILE_FIELD_LABELS = [
        'bio' => 'النبذة التعريفية',
        'country_id' => 'الدولة',
        'area_level_1' => 'المنطقة الأولى',
        'area_level_2' => 'المنطقة الثانية',
        'area_level_3' => 'المنطقة الثالثة',
        'locality' => 'الحي / المحلية',
        'car_type' => 'نوع السيارة',
        'car_model' => 'موديل السيارة',
        'car_model_year' => 'سنة الموديل',
        'car_year' => 'سنة الصنع',
        'car_plate_number' => 'رقم اللوحة',
        'has_driving_license' => 'رخصة القيادة',
        'license_number' => 'رقم الرخصة',
        'license_expiry_date' => 'تاريخ انتهاء الرخصة',
        'car_available' => 'توفر سيارة للتدريب',
        'pickup_available' => 'خدمة التوصيل',
    ];

    public function index(Request $request)
    {
        $q = User::query()->with([
            'roles',
            'country:id,name',
            'trainerProfile:id,user_id,pending_approval,country_id,area_level_2',
            'trainerProfile.country:id,name',
            'latestUserRequest',
            'latestUserRequest.country:id,name',
            'profilePicture:id,path,disk',
        ]);

        $role = $request->query('role');
        if ($role === 'trainer') {
            $q->trainerAccount();
        } elseif ($role === 'admin') {
            $q->role('ADMIN');
        } elseif ($role === 'user') {
            $q->studentAccount();
        }

        $status = $request->query('status');
        if ($status === 'banned') {
            $q->withTrashed();
            $q->where(function ($w) {
                $w->whereNotNull('deleted_at')
                  ->orWhere('banned_until', '>', now());
            });
        } elseif (in_array($status, ['pending_trainer', 'activation_required'], true)) {
            $q->trainerAccount()
              ->whereHas('trainerProfile', function ($profileQuery) {
                  $profileQuery->where('pending_approval', true);
              });
        } elseif ($status === 'active') {
            $q->whereNull('deleted_at')
              ->where(function ($w) {
                  $w->whereNull('banned_until')->orWhere('banned_until', '<=', now());
              });
        }

        $s = (string) $request->query('search', '');
        if ($s !== '') {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                ->orWhere('phone_with_cc', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%"));
        }

        $countryId = $request->query('country_id');
        if ($countryId) {
            $q->where(function ($w) use ($countryId) {
                $w->where('country_id', $countryId)
                    ->orWhereHas('trainerProfile', fn ($profileQuery) => $profileQuery->where('country_id', $countryId))
                    ->orWhereHas('userRequests', fn ($requestQuery) => $requestQuery->where('country_id', $countryId));
            });
        }

        $city = trim((string) $request->query('city', ''));
        if ($city !== '') {
            $q->where(function ($w) use ($city) {
                $w->whereHas('trainerProfile', fn ($profileQuery) => $profileQuery->where('area_level_2', 'like', "%{$city}%"))
                    ->orWhereHas('userRequests', fn ($requestQuery) => $requestQuery->where('area_level_2', 'like', "%{$city}%"));
            });
        }

        $users = $q->latest()->paginate(20)->withQueryString();

        $totalUsers = User::count();
        $trainersCount = User::query()->trainerAccount()->count();
        $normalUsersCount = User::query()->studentAccount()->count();
        $bannedCount = User::withTrashed()
            ->where(function ($w) {
                $w->whereNotNull('deleted_at')
                  ->orWhere('banned_until', '>', now());
            })->count();
        $pendingTrainersCount = User::query()->trainerAccount()
            ->whereHas('trainerProfile', function ($profileQuery) {
                $profileQuery->where('pending_approval', true);
            })->count();
        $activeCount = $totalUsers - $bannedCount;

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);
        $cityOptions = TrainerProfile::query()
            ->whereNotNull('area_level_2')
            ->pluck('area_level_2')
            ->merge(UserRequest::query()->whereNotNull('area_level_2')->pluck('area_level_2'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.users.index', compact(
            'users',
            'totalUsers',
            'trainersCount',
            'normalUsersCount',
            'bannedCount',
            'pendingTrainersCount',
            'activeCount',
            'role',
            'status',
            's',
            'countryId',
            'city',
            'countries',
            'cityOptions'
        ));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name');
        $countries = Country::orderBy('name')->get(['id','name']);
        return view('admin.users.create', compact('roles','countries'));
    }

    public function store(AdminStoreUserRequest $request)
    {
        $data = $request->validated();
        $user = new User();
        $user->fill([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone_with_cc' => $data['phone_with_cc'],
            'country_id' => $data['country_id'] ?? null,
            'whatsapp_enabled' => (bool)($data['whatsapp_enabled'] ?? false),
        ]);
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $this->syncProfilePicture($user, $request->file('profile_picture'));
        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        if (!empty($data['banned_until'])) {
            $user->update([
                'banned_until' => $data['banned_until'],
                'banned_reason' => $data['banned_reason'] ?? null,
            ]);
        }

        return redirect()->route('admin.users.index')->with('status','تم إنشاء المستخدم');
    }

    public function show(string $id)
    {
        $user = User::withTrashed()
            ->with([
                'roles',
                'profilePicture',
                'trainerProfile.country',
                'referrer:id,name,phone_with_cc,referral_code',
            ])
            ->findOrFail($id);

        // Get user requests for students
        $userRequests = UserRequest::with(['country', 'plan.country', 'trainer'])
            ->where('user_id', $id)
            ->latest()
            ->get();

        $referredUsers = User::withTrashed()
            ->with('roles')
            ->select('id', 'name', 'phone_with_cc', 'referred_by', 'created_at', 'deleted_at')
            ->selectSub(
                Payment::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('payments.user_id', 'users.id')
                    ->where('payments.type', Payment::TYPE_PLAN_FULL)
                    ->where('payments.status', Payment::STATUS_SUCCEEDED),
                'successful_full_payments_count'
            )
            ->selectSub(
                Payment::query()
                    ->selectRaw('COALESCE(SUM(amount_minor), 0)')
                    ->whereColumn('payments.user_id', 'users.id')
                    ->where('payments.type', Payment::TYPE_PLAN_FULL)
                    ->where('payments.status', Payment::STATUS_SUCCEEDED),
                'successful_full_payments_total_minor'
            )
            ->selectSub(
                UserRequest::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('user_requests.user_id', 'users.id')
                    ->whereIn('user_requests.status', [
                        UserRequest::STATUS_IN_TRAINING,
                        UserRequest::STATUS_COMPLETED,
                    ]),
                'paid_subscriptions_count'
            )
            ->where('referred_by', $user->id)
            ->latest()
            ->get();

        $trainerProfileView = $this->buildTrainerProfileView($user->trainerProfile);

        return view('admin.users.show', compact(
            'user',
            'userRequests',
            'trainerProfileView',
            'referredUsers'
        ));
    }

    public function approveTrainerProfile(string $id)
    {
        $user = User::withTrashed()->with('trainerProfile')->findOrFail($id);
        $hadPendingDetails = is_array($user->trainerProfile?->pending_changes) && !empty($user->trainerProfile->pending_changes);

        abort_unless($this->approveTrainer($user), 422, 'لا توجد موافقة معلقة لهذا المدرب');

        return back()->with('status', $hadPendingDetails
            ? 'تم الموافقة على تعديلات ملف المدرب'
            : 'تم تنشيط حساب المدرب بنجاح'
        );
    }

    public function rejectTrainerProfile(Request $request, string $id)
    {
        $user = User::withTrashed()->with('trainerProfile')->findOrFail($id);
        
        abort_unless($user->isTrainerAccount(), 422, 'المستخدم ليس مدرباً');
        abort_unless($user->trainerProfile && $user->trainerProfile->pending_approval, 422, 'لا توجد موافقة معلقة لهذا المدرب');

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $profile = $user->trainerProfile;
            
            // Clear pending changes
            $profile->pending_approval = false;
            $profile->pending_changes = null;
            $profile->pending_approval_at = null;
            $profile->save();

            // Keep user banned with reason
            $user->update([
                'banned_reason' => $validated['rejection_reason'],
            ]);
        });

        $user->notify(new TrainerProfileApprovalNotification(
            Auth::user(),
            false,
            $validated['rejection_reason'],
        ));

        return back()->with('status', 'تم رفض تعديلات ملف المدرب');
    }

    public function edit(string $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $roles = Role::orderBy('name')->pluck('name');
        $countries = Country::orderBy('name')->get(['id','name']);
        return view('admin.users.edit', compact('user','roles','countries'));
    }

    public function update(string $id, AdminUpdateUserRequest $request)
    {
        $user = User::withTrashed()->findOrFail($id);
        $data = $request->validated();
        $user->fill([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone_with_cc' => $data['phone_with_cc'],
            'country_id' => $data['country_id'] ?? null,
            'whatsapp_enabled' => (bool)($data['whatsapp_enabled'] ?? false),
        ]);
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $this->syncProfilePicture($user, $request->file('profile_picture'));
        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        $user->update([
            'banned_until' => $data['banned_until'] ?? null,
            'banned_reason' => $data['banned_reason'] ?? null,
        ]);

        return redirect()->route('admin.users.index')->with('status','تم تحديث المستخدم');
    }

    public function freeze(string $id)
    {
        $user = User::findOrFail($id);
        if (Auth::id() === $user->id) {
            return back()->withErrors(['self_delete' => 'لا يمكنك حذف حسابك من لوحة التحكم.']);
        }
        $user->delete();
        return back()->with('status', 'تم تجميد المستخدم بنجاح');
    }

    public function forceDestroy(string $id, UserPurgeService $purgeService)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (Auth::id() === $user->id) {
            return back()->withErrors(['self_delete' => 'لا يمكنك حذف حسابك الحالي نهائياً من لوحة التحكم.']);
        }

        if ($user->hasRole('ADMIN')) {
            return back()->withErrors(['force_delete' => 'لا يمكن حذف حساب إداري نهائياً.']);
        }

        $purgeService->purgeUser($user);

        return redirect()->route('admin.users.index')->with('status', 'تم حذف المستخدم وتحرير رقم الجوال مع الحفاظ على السجلات المالية السابقة.');
    }

    public function resetAll(Request $request, UserPurgeService $purgeService)
    {
        $request->validate([
            'confirm_reset' => ['accepted'],
        ], [], [
            'confirm_reset' => 'تأكيد إعادة التهيئة',
        ]);

        $summary = $purgeService->resetOperationalData(Auth::id());

        return redirect()->route('admin.dashboard')->with(
            'status',
            'تمت إعادة تهيئة البيانات بالكامل وحذف الحجوزات والمدفوعات والمحافظ والطلبات التشغيلية. عدد المستخدمين المحذوفين: ' . number_format($summary['users_deleted'])
        );
    }

    public function ban(string $id, Request $request)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'until' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $user->update([
            'banned_until' => $data['until'],
            'banned_reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'تم حظر المستخدم حتى '.$user->banned_until->format('Y-m-d H:i'));
    }

    public function unban(string $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->update([
            'banned_until' => null,
            'banned_reason' => null,
        ]);
        if ($user->trashed()) {
            $user->restore();
        }
        return back()->with('status', 'تم إلغاء حظر المستخدم بنجاح');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'uuid', 'exists:users,id'],
            'action' => ['required', 'string', 'in:ban,unban,delete,approve_trainers'],
        ], [
            'user_ids.required' => 'حقل المستخدمون المحددون مطلوب.',
            'action.required' => 'حقل الإجراء مطلوب.',
        ], [
            'user_ids' => 'المستخدمون المحددون',
            'user_ids.*' => 'المستخدم المحدد',
            'action' => 'الإجراء',
        ]);

        $userIds = $validated['user_ids'];
        $action = $validated['action'];
        $currentUserId = Auth::id();
        
        // Remove current user from selection to prevent self-action
        $userIds = array_filter($userIds, fn($id) => $id !== $currentUserId);
        
        if (empty($userIds)) {
            return back()->withErrors(['error' => 'لا يمكنك تنفيذ هذا الإجراء على نفسك']);
        }

        $users = User::withTrashed()
            ->with('trainerProfile')
            ->whereIn('id', $userIds)
            ->get();
        $count = 0;

        foreach ($users as $user) {
            if ($action === 'ban') {
                $user->update([
                    'banned_until' => now()->addYears(10),
                    'banned_reason' => 'حظر جماعي من قبل المدير',
                ]);
                $count++;
            } elseif ($action === 'unban') {
                $user->update([
                    'banned_until' => null,
                    'banned_reason' => null,
                ]);
                if ($user->trashed()) {
                    $user->restore();
                }
                $count++;
            } elseif ($action === 'delete') {
                if ($user->id !== Auth::id()) {
                    app(UserPurgeService::class)->purgeUser($user);
                    $count++;
                }
            } elseif ($action === 'approve_trainers' && $this->approveTrainer($user)) {
                $count++;
            }
        }

        $messages = [
            'ban' => "تم حظر {$count} مستخدم بنجاح",
            'unban' => "تم إلغاء حظر {$count} مستخدم بنجاح",
            'delete' => "تم حذف {$count} مستخدم بنجاح",
            'approve_trainers' => "تم قبول {$count} مدرب بنجاح",
        ];

        return back()->with('status', $messages[$action] ?? 'تم تنفيذ الإجراء بنجاح');
    }

    private function approveTrainer(User $user): bool
    {
        if (!$user->isTrainerAccount() || !$user->trainerProfile || !$user->trainerProfile->pending_approval) {
            return false;
        }

        DB::transaction(function () use ($user) {
            $profile = $user->trainerProfile;

            if (is_array($profile->pending_changes) && !empty($profile->pending_changes)) {
                $profile->fill($profile->pending_changes);
            }

            $profile->pending_approval = false;
            $profile->pending_changes = null;
            $profile->pending_approval_at = null;
            $profile->verified_at = $profile->verified_at ?? now();
            $profile->save();

            $user->update([
                'banned_until' => null,
                'banned_reason' => null,
            ]);

            if ($user->trashed()) {
                $user->restore();
            }
        });

        $user->notify(new TrainerProfileApprovalNotification(Auth::user(), true));

        return true;
    }

    private function buildTrainerProfileView(?TrainerProfile $profile): ?array
    {
        if (!$profile) {
            return null;
        }

        $pendingChanges = is_array($profile->pending_changes) ? $profile->pending_changes : [];
        $pendingChanges = array_filter(
            $pendingChanges,
            static fn (string $field): bool => array_key_exists($field, self::TRAINER_PROFILE_FIELD_LABELS),
            ARRAY_FILTER_USE_KEY
        );
        $hasPendingChanges = (bool) $profile->pending_approval;
        $pendingCountryName = null;

        if (array_key_exists('country_id', $pendingChanges) && filled($pendingChanges['country_id'])) {
            $pendingCountryName = Country::query()->where('id', $pendingChanges['country_id'])->value('name');
        }

        $resolveValue = function (string $field) use ($profile, $pendingChanges) {
            if (array_key_exists($field, $pendingChanges)) {
                return $pendingChanges[$field];
            }

            return $profile->getAttribute($field);
        };

        $display = [
            'car_type' => $resolveValue('car_type'),
            'car_model' => $resolveValue('car_model'),
            'car_model_year' => $resolveValue('car_model_year'),
            'car_year' => $resolveValue('car_year'),
            'car_plate_number' => $resolveValue('car_plate_number'),
            'has_driving_license' => (bool) $resolveValue('has_driving_license'),
            'license_number' => $resolveValue('license_number'),
            'license_expiry_date' => $this->formatTrainerFieldValue('license_expiry_date', $resolveValue('license_expiry_date')),
            'car_available' => (bool) $resolveValue('car_available'),
            'pickup_available' => (bool) $resolveValue('pickup_available'),
            'country_name' => array_key_exists('country_id', $pendingChanges)
                ? ($pendingCountryName ?: '-')
                : ($profile->country?->name ?? '-'),
            'area_level_1' => $resolveValue('area_level_1'),
            'area_level_2' => $resolveValue('area_level_2'),
            'area_level_3' => $resolveValue('area_level_3'),
            'locality' => $resolveValue('locality'),
            'bio' => $resolveValue('bio'),
        ];

        $changes = [];
        foreach ($pendingChanges as $field => $newValue) {
            $changes[] = [
                'field' => $field,
                'label' => self::TRAINER_PROFILE_FIELD_LABELS[$field] ?? $field,
                'old' => $this->formatTrainerFieldValue($field, $profile->getAttribute($field), $profile->country?->name),
                'new' => $this->formatTrainerFieldValue($field, $newValue, $pendingCountryName),
            ];
        }

        return [
            'has_pending_changes' => $hasPendingChanges,
            'display' => $display,
            'changes' => $changes,
        ];
    }

    private function formatTrainerFieldValue(
        string $field,
        mixed $value,
        ?string $countryName = null
    ): string {
        if (in_array($field, ['has_driving_license', 'car_available', 'pickup_available'], true)) {
            return $value ? 'نعم' : 'لا';
        }

        if ($field === 'country_id') {
            return $countryName ?: '-';
        }

        if ($field === 'license_expiry_date') {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return filled($value) ? (string) $value : '-';
        }

        return filled($value) ? (string) $value : '-';
    }

    private function syncProfilePicture(User $user, ?UploadedFile $profilePicture): void
    {
        if (! $profilePicture) {
            return;
        }

        if ($user->profile_picture_id) {
            $oldUpload = Upload::find($user->profile_picture_id);
            if ($oldUpload) {
                Storage::disk($oldUpload->disk)->delete($oldUpload->path);
                $oldUpload->delete();
            }
        }

        $disk = config('filesystems.default', 'public');
        $path = $profilePicture->store('profiles', $disk);
        $upload = Upload::create([
            'disk' => $disk,
            'path' => $path,
            'mime' => $profilePicture->getMimeType(),
            'size' => $profilePicture->getSize(),
        ]);

        $user->profile_picture_id = $upload->id;
    }
}
