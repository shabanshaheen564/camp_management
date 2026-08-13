<?php

namespace App\Support;

final class NotificationSections
{
    public const CAMPS = 'camps';
    public const FAMILIES = 'families';
    public const FAMILIES_TRASH = 'families.trash';
    public const AID = 'aid';
    public const USERS = 'users';
    public const ROLES = 'roles';
    public const NOTIFICATIONS = 'notifications';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CAMPS,
            self::FAMILIES,
            self::FAMILIES_TRASH,
            self::AID,
            self::USERS,
            self::ROLES,
            self::NOTIFICATIONS,
        ];
    }
}
