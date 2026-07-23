<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UserPurgeService
{
    public function purgeUser(User $user): void
    {
        $user = User::withTrashed()->findOrFail($user->id);
        $profileUpload = $user->profile_picture_id ? Upload::find($user->profile_picture_id) : null;
        $timestamp = now();
        $originalPhone = (string) $user->phone_with_cc;
        $originalEmail = (string) $user->email;

        DB::transaction(function () use ($user, $timestamp, $originalPhone, $originalEmail): void {
            DB::table('wallet_transactions')->where('processed_by', $user->id)->update(['processed_by' => null]);
            DB::table('cancellation_requests')->where('processed_by', $user->id)->update(['processed_by' => null]);
            DB::table('app_wallet_transactions')->where('created_by', $user->id)->update(['created_by' => null]);
            DB::table('trainer_profiles')->where('user_id', $user->id)->delete();
            DB::table('favorites')->where('user_id', $user->id)->orWhere('trainer_id', $user->id)->delete();
            DB::table('users')->where('referred_by', $user->id)->update(['referred_by' => null, 'updated_at' => $timestamp]);

            $this->deleteSupportDataForUser($user, $originalPhone, $originalEmail);

            $conversationIds = DB::table('conversations')
                ->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id)
                ->pluck('id')
                ->all();

            if ($conversationIds !== []) {
                DB::table('messages')->whereIn('conversation_id', $conversationIds)->delete();
                DB::table('conversations')->whereIn('id', $conversationIds)->delete();
            }

            DB::table('messages')->where('sender_id', $user->id)->delete();
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $user->id)
                ->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('user_device_tokens')->where('user_id', $user->id)->delete();
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->delete();

            $roleTable = config('permission.table_names.model_has_roles');
            $permissionTable = config('permission.table_names.model_has_permissions');
            $modelKey = (string) config('permission.column_names.model_morph_key', 'model_id');

            if (filled($roleTable)) {
                DB::table($roleTable)
                    ->where($modelKey, $user->id)
                    ->where('model_type', User::class)
                    ->delete();
            }

            if (filled($permissionTable)) {
                DB::table($permissionTable)
                    ->where($modelKey, $user->id)
                    ->where('model_type', User::class)
                    ->delete();
            }

            if ($user->profile_picture_id) {
                DB::table('uploads')->where('id', $user->profile_picture_id)->delete();
            }

            $user->forceFill([
                'name' => 'مستخدم محذوف',
                'email' => null,
                'phone_with_cc' => $this->releasedPhoneValue($user->id, $timestamp),
                'password' => Str::random(64),
                'whatsapp_enabled' => false,
                'profile_picture_id' => null,
                'bank_account' => null,
                'bank_account_name' => null,
                'iban' => null,
                'bank_name' => null,
                'bank_country_id' => null,
                'banned_until' => $this->deletedAccountBanUntil($timestamp),
                'banned_reason' => 'تم حذف الحساب وتحرير رقم الجوال',
            ])->save();

            if (! $user->trashed()) {
                $user->delete();
            }
        });

        if ($profileUpload) {
            Storage::disk($profileUpload->disk)->delete($profileUpload->path);
        }
    }

    public function purgeStandaloneSupportData(): void
    {
        DB::transaction(function (): void {
            DB::table('support_ticket_messages')->delete();
            DB::table('support_tickets')->delete();
        });
    }

    /**
     * @return array{users_deleted:int,data_deleted:bool}
     */
    public function resetOperationalData(
        ?string $currentAdminId = null,
        bool $deleteUsers = true,
        bool $deleteData = true,
    ): array {
        $adminIds = $this->adminUserIds($currentAdminId);

        if ($deleteUsers && ! $deleteData) {
            return [
                'users_deleted' => $this->purgeNonAdminUsersPreservingHistory($adminIds),
                'data_deleted' => false,
            ];
        }

        $nonAdminUsers = $deleteUsers
            ? User::withTrashed()
                ->whereNotIn('id', $adminIds)
                ->get(['id', 'profile_picture_id'])
            : collect();
        $uploadIds = $nonAdminUsers
            ->pluck('profile_picture_id')
            ->filter()
            ->unique()
            ->values();
        $profileUploads = Upload::query()
            ->whereIn('id', $uploadIds)
            ->get(['id', 'disk', 'path']);

        DB::transaction(function () use ($adminIds, $uploadIds, $deleteUsers, $deleteData): void {
            Schema::disableForeignKeyConstraints();

            try {
                if ($deleteData) {
                    $this->deleteOperationalDataForReset();
                }

                if ($deleteData && ! $deleteUsers) {
                    $this->resetNonAdminWalletBalances($adminIds);
                }

                if ($deleteUsers) {
                    $this->deleteNonAdminUsersForReset($adminIds, $uploadIds);
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });

        foreach ($profileUploads as $upload) {
            Storage::disk($upload->disk)->delete($upload->path);
        }

        return [
            'users_deleted' => $nonAdminUsers->count(),
            'data_deleted' => $deleteData,
        ];
    }

    private function adminUserIds(?string $currentAdminId = null): \Illuminate\Support\Collection
    {
        return User::withTrashed()
            ->whereHas('roles', fn ($query) => $query->where('name', 'ADMIN'))
            ->pluck('id')
            ->push($currentAdminId)
            ->filter()
            ->unique()
            ->values();
    }

    private function purgeNonAdminUsersPreservingHistory(\Illuminate\Support\Collection $adminIds): int
    {
        $users = User::withTrashed()
            ->whereNotIn('id', $adminIds)
            ->get(['id']);

        foreach ($users as $user) {
            $this->purgeUser($user);
        }

        return $users->count();
    }

    private function deleteOperationalDataForReset(): void
    {
        foreach ($this->operationalTablesForReset() as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function resetNonAdminWalletBalances(\Illuminate\Support\Collection $adminIds): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'points_balance')) {
            return;
        }

        $updates = ['points_balance' => 0];

        if (Schema::hasColumn('users', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('users')->whereNotIn('id', $adminIds)->update($updates);
    }

    private function deleteNonAdminUsersForReset(\Illuminate\Support\Collection $adminIds, \Illuminate\Support\Collection $uploadIds): void
    {
        if (Schema::hasTable('trainer_profiles')) {
            DB::table('trainer_profiles')
                ->whereNotIn('user_id', $adminIds)
                ->delete();
        }

        DB::table('users')
            ->whereIn('id', $adminIds)
            ->whereNotNull('referred_by')
            ->update(['referred_by' => null]);
        DB::table('users')->whereNotIn('id', $adminIds)->delete();

        $roleTable = config('permission.table_names.model_has_roles');
        $permissionTable = config('permission.table_names.model_has_permissions');
        $modelKey = (string) config('permission.column_names.model_morph_key', 'model_id');

        if (filled($roleTable) && Schema::hasTable($roleTable)) {
            DB::table($roleTable)
                ->where('model_type', User::class)
                ->whereNotIn($modelKey, $adminIds)
                ->delete();
        }

        if (filled($permissionTable) && Schema::hasTable($permissionTable)) {
            DB::table($permissionTable)
                ->where('model_type', User::class)
                ->whereNotIn($modelKey, $adminIds)
                ->delete();
        }

        if ($uploadIds->isNotEmpty() && Schema::hasTable('uploads')) {
            DB::table('uploads')->whereIn('id', $uploadIds)->delete();
        }
    }

    private function deleteSupportDataForUser(User $user, string $phone, string $email): void
    {
        $supportTicketIds = DB::table('support_tickets')
            ->where('user_id', $user->id)
            ->when($phone !== '', fn ($query) => $query->orWhere('phone_with_cc', $phone))
            ->when($email !== '', fn ($query) => $query->orWhere('email', $email))
            ->pluck('id')
            ->all();

        if ($supportTicketIds !== []) {
            DB::table('support_ticket_messages')->whereIn('ticket_id', $supportTicketIds)->delete();
            DB::table('support_tickets')->whereIn('id', $supportTicketIds)->delete();
        }

        DB::table('support_ticket_messages')->where('user_id', $user->id)->delete();
    }

    private function releasedPhoneValue(string $userId, \DateTimeInterface $timestamp): string
    {
        return 'deleted:'.$timestamp->format('YmdHis').':'.substr($userId, 0, 8);
    }

    private function deletedAccountBanUntil(\Illuminate\Support\Carbon $timestamp): \Illuminate\Support\Carbon
    {
        $safeTimestampLimit = $timestamp->copy()->setDate(2037, 12, 31)->endOfDay();
        $banUntil = $timestamp->copy()->addYears(10);

        return $banUntil->lessThanOrEqualTo($safeTimestampLimit) ? $banUntil : $safeTimestampLimit;
    }

    /**
     * @return list<string>
     */
    private function operationalTablesForReset(): array
    {
        return [
            'support_ticket_messages',
            'support_tickets',
            'messages',
            'conversations',
            'notifications',
            'user_device_tokens',
            'refresh_tokens',
            'personal_access_tokens',
            'favorites',
            'reward_redemptions',
            'ratings',
            'payouts',
            'cancellation_requests',
            'user_schedule_progress',
            'training_days',
            'trainer_offers',
            'payments',
            'wallet_transactions',
            'gateway_wallet_transactions',
            'app_wallet_transactions',
            'app_expenses',
            'user_requests',
        ];
    }
}
