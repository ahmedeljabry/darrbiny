<?php

declare(strict_types=1);

namespace App\Support;

final class AccessLabels
{
    /** @var array<string, string> */
    private const ROLE_LABELS = [
        'ADMIN' => 'مدير النظام',
        'TRAINER' => 'مدرب',
        'USER' => 'مستخدم',
    ];

    /** @var array<string, string> */
    private const PERMISSION_LABELS = [
        'view_admin' => 'عرض لوحة الإدارة',
        'manage_users' => 'إدارة المستخدمين',
        'manage_roles' => 'إدارة الأدوار',
        'manage_permissions' => 'إدارة الصلاحيات',
        'manage_plans' => 'إدارة الخطط',
        'manage_payments' => 'إدارة المدفوعات',
        'manage_reports' => 'إدارة التقارير',
        'manage_geo' => 'إدارة النطاق الجغرافي',
        'manage_ratings' => 'إدارة التقييمات',
        'manage_wallets' => 'إدارة المحافظ',
        'manage_notifications' => 'إدارة الإشعارات والتواصل',
        'manage_settings' => 'إدارة الإعدادات',
        'manage_payouts' => 'إدارة المستحقات',
        'manage_rewards' => 'إدارة المكافآت',
        'verify_trainers' => 'التحقق من المدربين',
        'cancel_courses' => 'إلغاء الدورات',
    ];

    public static function role(?string $name): string
    {
        if ($name === null || $name === '') {
            return '-';
        }

        return self::ROLE_LABELS[strtoupper($name)] ?? $name;
    }

    public static function permission(?string $name): string
    {
        if ($name === null || $name === '') {
            return '-';
        }

        return self::PERMISSION_LABELS[strtolower($name)] ?? $name;
    }

    public static function roleFilterValue(?string $name): ?string
    {
        return match (strtoupper((string) $name)) {
            'ADMIN' => 'admin',
            'TRAINER' => 'trainer',
            'USER' => 'user',
            default => null,
        };
    }

    public static function isCorePermission(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }

        return array_key_exists(strtolower($name), self::PERMISSION_LABELS);
    }
}

