<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class UserPurgeService
{
    public function purgeUser(User $user): void
    {
        $user = User::withTrashed()->findOrFail($user->id);
        $profileUpload = $user->profile_picture_id ? Upload::find($user->profile_picture_id) : null;
        $timestamp = now();

        DB::transaction(function () use ($user, $timestamp): void {
            $ownedRequestIds = DB::table('user_requests')
                ->where('user_id', $user->id)
                ->pluck('id')
                ->all();

            $trainerRequestIds = DB::table('user_requests')
                ->where('trainer_id', $user->id)
                ->pluck('id')
                ->all();

            if ($ownedRequestIds !== []) {
                DB::table('trainer_offers')->whereIn('user_request_id', $ownedRequestIds)->delete();
                DB::table('training_days')->whereIn('user_request_id', $ownedRequestIds)->delete();
                DB::table('user_schedule_progress')->whereIn('user_request_id', $ownedRequestIds)->delete();
                DB::table('payments')->whereIn('user_request_id', $ownedRequestIds)->delete();
                DB::table('cancellation_requests')->whereIn('user_request_id', $ownedRequestIds)->delete();
                DB::table('payouts')->whereIn('user_request_id', $ownedRequestIds)->delete();
                DB::table('ratings')->whereIn('user_request_id', $ownedRequestIds)->delete();
                DB::table('user_requests')->whereIn('id', $ownedRequestIds)->delete();
            }

            if ($trainerRequestIds !== []) {
                DB::table('user_requests')
                    ->whereIn('id', $trainerRequestIds)
                    ->update([
                        'trainer_id' => null,
                        'updated_at' => $timestamp,
                    ]);
            }

            DB::table('trainer_offers')->where('trainer_id', $user->id)->delete();
            DB::table('training_days')->where('trainer_id', $user->id)->delete();
            DB::table('payouts')->where('trainer_id', $user->id)->delete();
            DB::table('ratings')->where('user_id', $user->id)->orWhere('trainer_id', $user->id)->delete();
            DB::table('reward_redemptions')->where('user_id', $user->id)->delete();
            DB::table('wallet_transactions')->where('user_id', $user->id)->delete();
            DB::table('wallet_transactions')->where('processed_by', $user->id)->update(['processed_by' => null]);
            DB::table('cancellation_requests')->where('user_id', $user->id)->delete();
            DB::table('cancellation_requests')->where('processed_by', $user->id)->update(['processed_by' => null]);
            DB::table('trainer_profiles')->where('user_id', $user->id)->delete();
            DB::table('favorites')->where('user_id', $user->id)->orWhere('trainer_id', $user->id)->delete();
            DB::table('referrals')->where('owner_user_id', $user->id)->delete();
            DB::table('users')->where('referred_by', $user->id)->update(['referred_by' => null, 'updated_at' => $timestamp]);

            $supportTicketIds = DB::table('support_tickets')
                ->where('user_id', $user->id)
                ->pluck('id')
                ->all();

            if ($supportTicketIds !== []) {
                DB::table('support_ticket_messages')->whereIn('ticket_id', $supportTicketIds)->delete();
                DB::table('support_tickets')->whereIn('id', $supportTicketIds)->delete();
            }

            DB::table('support_ticket_messages')->where('user_id', $user->id)->delete();

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

            DB::table('users')->where('id', $user->id)->delete();
        });

        if ($profileUpload) {
            Storage::disk($profileUpload->disk)->delete($profileUpload->path);
        }
    }
}
